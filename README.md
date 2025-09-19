<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

# 📘 ERP System API Documentation

## 🔹 General Information

- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Content-Type**: `application/json`
- **Version**: v1.0
- **Rate Limiting**: 5 requests per minute (authentication endpoints), 3 requests per minute (password reset endpoints)

---

## 🔐 Authentication Endpoints



### 2. User Login

**POST** `/auth/login`

**Description**: Authenticate user and receive access token.

#### 🔑 Authentication
- **Required**: No

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Content-Type | string | Yes | application/json |

**Request Body**:
```json
{
  "email": "john.doe@example.com",
  "password": "password123"
}
```

**Field Descriptions**:
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| email | string | Yes | User's email address | "john.doe@example.com" |
| password | string | Yes | User's password (minimum 6 characters) | "password123" |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "User logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "created_at": "2025-09-19T12:00:00.000000Z",
      "updated_at": "2025-09-19T12:00:00.000000Z"
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

**Error (401 Unauthorized)**:
```json
{
  "status": "error",
  "message": "Invalid password",
  "data": ""
}
```

**Error (404 Not Found)**:
```json
{
  "status": "error",
  "message": "User not found",
  "data": ""
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X POST http://localhost:8000/api/auth/login \
-H "Content-Type: application/json" \
-d '{
  "email": "john.doe@example.com",
  "password": "password123"
}'
```

---

### 3. User Logout

**GET** `/auth/logout`

**Description**: Logout user and revoke current access token.

#### 🔑 Authentication
- **Required**: Yes (Bearer Token)

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Authorization | string | Yes | Bearer {token} |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "User logged out successfully",
  "data": ""
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X GET http://localhost:8000/api/auth/logout \
-H "Authorization: Bearer 1|abcdef123456..."
```

---

### 4. Get Authenticated User

**GET** `/auth/me`

**Description**: Get current authenticated user information.

#### 🔑 Authentication
- **Required**: Yes (Bearer Token)

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Authorization | string | Yes | Bearer {token} |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "created_at": "2025-09-19T12:00:00.000000Z",
    "updated_at": "2025-09-19T12:00:00.000000Z"
  }
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X GET http://localhost:8000/api/auth/me \
-H "Authorization: Bearer 1|abcdef123456..."
```

---

## 🔄 Password Reset Endpoints

### 5. Forgot Password (Send OTP)

**POST** `/auth/forgot-password`

**Description**: Send OTP to user's email for password reset.

#### 🔑 Authentication
- **Required**: No
- **Rate Limit**: 3 requests per minute

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Content-Type | string | Yes | application/json |

**Request Body**:
```json
{
  "email": "john.doe@example.com"
}
```

**Field Descriptions**:
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| email | string | Yes | User's registered email address | "john.doe@example.com" |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "OTP sent to your email successfully",
  "data": {
    "email": "john.doe@example.com",
    "expires_in": 45
  }
}
```

**Error (404 Not Found)**:
```json
{
  "status": "error",
  "message": "User not found with this email",
  "data": ""
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X POST http://localhost:8000/api/auth/forgot-password \
-H "Content-Type: application/json" \
-d '{
  "email": "john.doe@example.com"
}'
```

---

### 6. Verify OTP

**POST** `/auth/verify-otp`

**Description**: Verify the OTP sent to user's email.

#### 🔑 Authentication
- **Required**: No
- **Rate Limit**: 5 requests per minute

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Content-Type | string | Yes | application/json |

**Request Body**:
```json
{
  "email": "john.doe@example.com",
  "otp": "123456"
}
```

**Field Descriptions**:
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| email | string | Yes | User's email address | "john.doe@example.com" |
| otp | string | Yes | 6-digit OTP code | "123456" |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "OTP verified successfully. You can now reset your password.",
  "data": {
    "email": "john.doe@example.com",
    "verified": true
  }
}
```

**Error (400 Bad Request)**:
```json
{
  "status": "error",
  "message": "Invalid or expired OTP",
  "data": ""
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X POST http://localhost:8000/api/auth/verify-otp \
-H "Content-Type: application/json" \
-d '{
  "email": "john.doe@example.com",
  "otp": "123456"
}'
```

---

### 7. Reset Password

**POST** `/auth/reset-password`

**Description**: Reset user password after OTP verification.

#### 🔑 Authentication
- **Required**: No (but requires verified OTP)
- **Rate Limit**: 3 requests per minute

#### 📥 Request

**Headers**:
| Header | Type | Required | Description |
|--------|------|----------|-------------|
| Content-Type | string | Yes | application/json |

**Request Body**:
```json
{
  "email": "john.doe@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Field Descriptions**:
| Field | Type | Required | Description | Example |
|-------|------|----------|-------------|---------|
| email | string | Yes | User's email address | "john.doe@example.com" |
| password | string | Yes | New password (minimum 8 characters) | "newpassword123" |
| password_confirmation | string | Yes | Password confirmation (must match password) | "newpassword123" |

#### 📤 Response

**Success (200 OK)**:
```json
{
  "status": "success",
  "message": "Password reset successfully. Please login with your new password.",
  "data": ""
}
```

**Error (400 Bad Request)**:
```json
{
  "status": "error",
  "message": "No verified OTP found. Please verify OTP first.",
  "data": ""
}
```

#### 📊 Example Request

**cURL**:
```bash
curl -X POST http://localhost:8000/api/auth/reset-password \
-H "Content-Type: application/json" \
-d '{
  "email": "john.doe@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}'
```

---

## 📝 HTTP Status Codes

| Code | Message | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Validation error or invalid request |
| 401 | Unauthorized | Invalid or missing authentication token |
| 404 | Not Found | Resource not found |
| 500 | Internal Server Error | Something went wrong on the server |

---

## 🔒 Error Response Format

All error responses follow this standard format:

```json
{
  "status": "error",
  "message": "Error description",
  "data": ""
}
```

---

## 📌 Additional Notes

- **Rate Limiting**: 
  - Authentication endpoints: 5 requests per minute
  - Password reset endpoints: 3 requests per minute (forgot-password, reset-password), 5 requests per minute (verify-otp)
- **Token Management**: All existing tokens are revoked when user logs in or resets password
- **OTP Expiry**: OTP codes expire after 45 seconds
- **Authentication**: Use Bearer token in Authorization header for protected endpoints
- **Validation**: All validation errors return 400 status with detailed error messages
- **Security**: Passwords are hashed using Laravel's Hash facade (bcrypt)

---

## 🚀 Quick Start Example

Here's a complete workflow example:

```javascript
// 1. Register a new user
const registerResponse = await fetch("http://localhost:8000/api/auth/register", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    first_name: "John",
    last_name: "Doe",
    email: "john.doe@example.com",
    password: "password123",
    password_confirmation: "password123"
  })
});

// 2. Use the token for authenticated requests
const { token } = (await registerResponse.json()).data;

// 3. Get user info
const userResponse = await fetch("http://localhost:8000/api/auth/me", {
  headers: { "Authorization": `Bearer ${token}` }
});

// 4. Logout
await fetch("http://localhost:8000/api/auth/logout", {
  headers: { "Authorization": `Bearer ${token}` }
});
```
