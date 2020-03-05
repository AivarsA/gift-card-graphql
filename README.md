# ScandiPWA_GiftCardGraphQl

**GiftCardGraphQl** provides resolvers for Gift Card.

> **IMPORTANT NOTE**: This module depends on ScandiPWA_QuoteGraphQl

### applyGiftCard

This endpoint allows to apply gift card to cart

```graphql
mutation applyGiftCard(guestCartId: String, gift_card_code: String!) {
    cart {
      applied_gift_cards {
        code
        applied_balance {
          currency
          value
        }
        expiration_date
        current_balance {
          currency
          value
        }
      }
    }
  }
```
### removeGiftCard

This endpoint allows to remove applied gift card from cart

```graphql
mutation removeGiftCard(guestCartId: String, gift_card_code: String!) {
    cart {
      applied_gift_cards {
        code
        applied_balance {
          currency
          value
        }
        expiration_date
        current_balance {
          currency
          value
        }
      }
    }
  }
```

### applied_gift_cards

This endpoint allows to retrieve all gift cards which have been applied to cart

```graphql
mutation applied_gift_cards(guestCartId: String) {
    cart {
      applied_gift_cards {
        code
        applied_balance {
          currency
          value
        }
        expiration_date
        current_balance {
          currency
          value
        }
      }
    }
  }
```
