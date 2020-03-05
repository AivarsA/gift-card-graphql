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
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Webapi\Controller\Rest\ParamOverriderCustomerId;
use ScandiPWA\QuoteGraphQl\Model\Resolver\CartCouponException;
use ScandiPWA\QuoteGraphQl\Model\Resolver\CartResolver;

/**
 * Class RemoveCartItem
 * @package ScandiPWA\GiftCardGraphQl\Model\Resolver
 */
class RemoveGiftCard extends CartResolver
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    private $giftCardAccountManagement;

    /**
     * RemoveCartItem constructor.
     *
     * @param ParamOverriderCustomerId $overriderCustomerId
     * @param CartManagementInterface $quoteManagement
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param GiftCardAccountManagementInterface $giftCardAccountManagement
     */
    public function __construct(
        ParamOverriderCustomerId $overriderCustomerId,
        CartManagementInterface $quoteManagement,
        GuestCartRepositoryInterface $guestCartRepository,
        GiftCardAccountManagementInterface $giftCardAccountManagement
    )
    {
        parent::__construct($guestCartRepository, $overriderCustomerId, $quoteManagement);
        $this->giftCardAccountManagement = $giftCardAccountManagement;
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

        if ($cart->getItemsCount() < 1) {
            throw new CartCouponException(__("Cart does not contain products"));
        }

        $giftCardCode = $args['gift_card_code'];
        $cartId = $cart->getId();

        try {
            $this->giftCardAccountManagement->deleteByQuoteId($cartId, $giftCardCode);
        } catch (CouldNotDeleteException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return [];
    }
}
