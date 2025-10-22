# 📚 Coprra API Documentation

Version: 2.0.0  
Base URL: `https://api.coprra.com/api`  
Authentication: Bearer Token (Laravel Sanctum)

---

## 📋 Table of Contents

- [Authentication](#authentication)
- [Orders](#orders)
- [Products](#products)
- [Cart](#cart)
- [Users](#users)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)

---

## 🔐 Authentication

### Register

Create a new user account.

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Response:** `201 Created`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": {
      "value": "user",
      "label": "مستخدم"
    },
    "created_at": "2025-10-01T10:00:00Z"
  },
  "token": "1|abc123..."
}
```

**Rate Limit:** 3 attempts per minute

---

### Login

Authenticate and receive access token.

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Response:** `200 OK`
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": {
      "value": "user",
      "label": "مستخدم"
    }
  },
  "token": "1|abc123..."
}
```

**Rate Limit:** 5 attempts per minute

---

### Logout

Revoke current access token.

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

## 📦 Orders

### List Orders

Get all orders for authenticated user.

**Endpoint:** `GET /api/orders`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional) - Filter by status: `pending`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`
- `page` (optional) - Page number (default: 1)
- `per_page` (optional) - Items per page (default: 15)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-ABC123",
      "status": {
        "value": "processing",
        "label": "قيد المعالجة",
        "color": "blue"
      },
      "total_amount": 150.00,
      "subtotal": 130.00,
      "tax_amount": 15.00,
      "shipping_amount": 5.00,
      "discount_amount": 0.00,
      "currency": "USD",
      "created_at": "2025-10-01T10:00:00Z",
      "items": [
        {
          "id": 1,
          "product_id": 10,
          "quantity": 2,
          "price": 65.00,
          "subtotal": 130.00
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 10,
    "per_page": 15
  }
}
```

---

### Get Order

Get single order details.

**Endpoint:** `GET /api/orders/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "order_number": "ORD-ABC123",
    "status": {
      "value": "processing",
      "label": "قيد المعالجة",
      "color": "blue"
    },
    "total_amount": 150.00,
    "items": [...],
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

---

### Create Order

Create a new order from cart items.

**Endpoint:** `POST /api/orders`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 10,
      "quantity": 2
    }
  ],
  "shipping_address": {
    "street": "123 Main St",
    "city": "New York",
    "state": "NY",
    "zip": "10001",
    "country": "USA"
  },
  "billing_address": {
    "street": "123 Main St",
    "city": "New York",
    "state": "NY",
    "zip": "10001",
    "country": "USA"
  },
  "notes": "Please deliver before 5 PM"
}
```

**Response:** `201 Created`
```json
{
  "data": {
    "id": 1,
    "order_number": "ORD-ABC123",
    "status": {
      "value": "pending",
      "label": "قيد الانتظار",
      "color": "yellow"
    },
    "total_amount": 150.00
  }
}
```

---

### Update Order Status

Update order status (Admin only).

**Endpoint:** `PATCH /api/orders/{id}/status`

**Headers:**
```
Authorization: Bearer {token}
```

**Middleware:** `role:admin,moderator`

**Request Body:**
```json
{
  "status": "shipped"
}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "status": {
      "value": "shipped",
      "label": "تم الشحن",
      "color": "purple"
    },
    "shipped_at": "2025-10-01T12:00:00Z"
  }
}
```

**Validation:**
- Status must be valid enum value
- Transition must be allowed (e.g., can't go from `delivered` to `pending`)

---

## 🛍️ Products

### List Products

Get paginated list of products.

**Endpoint:** `GET /api/products`

**Query Parameters:**
- `search` (optional) - Search in name/description
- `category_id` (optional) - Filter by category
- `brand_id` (optional) - Filter by brand
- `min_price` (optional) - Minimum price
- `max_price` (optional) - Maximum price
- `is_featured` (optional) - Filter featured products (0/1)
- `sort` (optional) - Sort by: `price_asc`, `price_desc`, `newest`, `popular`
- `page` (optional) - Page number
- `per_page` (optional) - Items per page (max: 100)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 10,
      "name": "Product Name",
      "slug": "product-name",
      "description": "Product description",
      "price": 65.00,
      "compare_price": 80.00,
      "sku": "PROD-001",
      "quantity": 50,
      "is_active": true,
      "is_featured": true,
      "image": "https://...",
      "rating": 4.5,
      "reviews_count": 120,
      "category": {
        "id": 1,
        "name": "Electronics"
      },
      "brand": {
        "id": 1,
        "name": "Brand Name"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 15
  }
}
```

---

## 🛒 Cart

### Get Cart

Get current user's cart.

**Endpoint:** `GET /api/cart`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "items": [
    {
      "id": 1,
      "product_id": 10,
      "quantity": 2,
      "product": {
        "id": 10,
        "name": "Product Name",
        "price": 65.00,
        "image": "https://..."
      }
    }
  ],
  "totals": {
    "subtotal": 130.00,
    "tax": 15.00,
    "shipping": 5.00,
    "total": 150.00
  }
}
```

---

### Add to Cart

Add product to cart.

**Endpoint:** `POST /api/cart`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "product_id": 10,
  "quantity": 2
}
```

**Response:** `201 Created`

---

### Update Cart Item

Update quantity of cart item.

**Endpoint:** `PATCH /api/cart/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "quantity": 3
}
```

**Validation:**
- `quantity` must be between 1 and 999

**Response:** `200 OK`

---

### Remove from Cart

Remove item from cart.

**Endpoint:** `DELETE /api/cart/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `204 No Content`

---

## 👤 Users

### Get Current User

Get authenticated user profile.

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": {
      "value": "user",
      "label": "مستخدم"
    },
    "avatar": "https://...",
    "phone": "+1234567890",
    "is_active": true,
    "email_verified_at": "2025-10-01T10:00:00Z",
    "created_at": "2025-10-01T10:00:00Z"
  }
}
```

---

## ⚠️ Error Handling

### Error Response Format

All errors follow this format:

```json
{
  "message": "Error message",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Server Error

---

## 🚦 Rate Limiting

### Limits

- **Authentication endpoints:** 3-5 requests per minute
- **API endpoints:** 60 requests per minute
- **Guest endpoints:** 30 requests per minute

### Headers

Rate limit information is included in response headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1633024800
```

### Rate Limit Exceeded

**Response:** `429 Too Many Requests`
```json
{
  "message": "Too many requests. Please try again later."
}
```

---

## 📝 Notes

- All timestamps are in ISO 8601 format (UTC)
- All monetary values are in decimal format
- Pagination uses standard Laravel format
- Authentication uses Laravel Sanctum tokens
- CORS is enabled for allowed origins

---

**Last Updated:** 2025-10-01  
**API Version:** 2.0.0

