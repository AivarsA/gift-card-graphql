<?php
/**
 * ScandiPWA - Progressive Web App for Magento
 *
 * Copyright © Scandiweb, Inc. All rights reserved.
 * See LICENSE for license details.
 *
 * @license OSL-3.0 (Open Software License ("OSL") v. 3.0)
 * @package scandipwa/gift-card-graphql
 * @link    https://github.com/scandipwa/gift-card-graphql
 */

declare(strict_types=1);

namespace ScandiPWA\GiftCardGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount as ModelGiftcardaccount;
use Magento\GiftCardAccountGraphQl\Model\Money\Formatter as MoneyFormatter;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\Cart\TotalSegment;
use Magento\Webapi\Controller\Rest\ParamOverriderCustomerId;
use ScandiPWA\QuoteGraphQl\Model\Resolver\CartResolver;

/**
 * Class GetAppliedGiftCards
 * @package ScandiPWA\GiftCardGraphQl\Model\Resolver
 */
class GetAppliedGiftCards extends CartResolver
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    private $giftCardAccountManagement;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $cartTotalRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var MoneyFormatter
     */
    private $moneyFormatter;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $giftCardAccountRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * GetAppliedGiftCards constructor.
     *
     * @param ParamOverriderCustomerId $overriderCustomerId
     * @param CartManagementInterface $quoteManagement
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param GiftCardAccountManagementInterface $giftCardAccountManagement
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param Json $json
     * @param GiftCardAccountRepositoryInterface $giftCardAccountRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param MoneyFormatter $moneyFormatter
     */
    public function __construct(
        ParamOverriderCustomerId $overriderCustomerId,
        CartManagementInterface $quoteManagement,
        GuestCartRepositoryInterface $guestCartRepository,
        GiftCardAccountManagementInterface $giftCardAccountManagement,
        CartTotalRepositoryInterface $cartTotalRepository,
        Json $json,
        GiftCardAccountRepositoryInterface $giftCardAccountRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        MoneyFormatter $moneyFormatter
    )
    {
        parent::__construct($guestCartRepository, $overriderCustomerId, $quoteManagement);
        $this->giftCardAccountManagement = $giftCardAccountManagement;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->json = $json;
        $this->giftCardAccountRepository = $giftCardAccountRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->moneyFormatter = $moneyFormatter;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $cart = $this->getCart($args);

        $cartId = (string) $cart->getId();

        $giftCardAccount = $this->giftCardAccountManagement->getListByQuoteId($cartId);

        $giftCardAccounts = $this->getByCodes($giftCardAccount->getGiftCards());

        $cartGiftCards = $this->getGiftCardSegmentsFromCart($cartId);
        $appliedGiftCards = [];
        $store = $context->getExtensionAttributes()->getStore();

        foreach ($giftCardAccounts as $giftAccount) {
            $appliedGiftCards[]= [
                'code' => $giftAccount->getCode(),
                'current_balance' => $this->moneyFormatter->formatAmountAsMoney($giftAccount->getBalance(), $store),
                'applied_balance' => $this->moneyFormatter->formatAmountAsMoney(
                    $cartGiftCards[$giftAccount->getCode()][ModelGiftcardaccount::AMOUNT],
                    $store
                ),
                'expiration_date' => $giftAccount->getDateExpires(),
            ];
        }

        return $appliedGiftCards;
    }

    /**
     * Get giftcard segments from the cart
     *
     * @param string $cartId
     * @return array
     * @throws NoSuchEntityException
     */
    private function getGiftCardSegmentsFromCart(string $cartId)
    {
        $cartTotal = $this->cartTotalRepository->get($cartId);
        $totalSegments = $cartTotal->getTotalSegments();
        $cartGiftCards = [];
        if (isset($totalSegments['giftcardaccount'])) {
            /** @var TotalSegment $totalSegment */
            $totalSegment = $totalSegments['giftcardaccount'];
            $extensionAttributes = $totalSegment->getExtensionAttributes();
            $giftCardsTotals = $this->json->unserialize($extensionAttributes->getGiftCards());
            if (is_array($giftCardsTotals)) {
                foreach ($giftCardsTotals as $giftCardTotal) {
                    if (isset($giftCardTotal[ModelGiftcardaccount::CODE])) {
                        $cartGiftCards[$giftCardTotal[ModelGiftcardaccount::CODE]] = $giftCardTotal;
                    }
                }
            }
        }

        return $cartGiftCards;
    }

    /**
     * Retrieve set of giftcard accounts based on the codes
     *
     * @param array $giftCardCodes
     * @return array
     */
    private function getByCodes(array $giftCardCodes): array
    {
        return $this->giftCardAccountRepository->getList(
            $this->criteriaBuilder->addFilter('code', $giftCardCodes, 'in')->create()
        )->getItems();
    }
}
