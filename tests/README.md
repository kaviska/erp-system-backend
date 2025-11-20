# Authentication API Test Suite

This comprehensive test suite contains over 100 test cases for all authentication endpoints in the ERP system.

## Test Coverage

### 1. Login Endpoint (`/api/auth/login`) - 25 Test Cases
- **File**: `tests/Feature/AuthLoginTest.php`
- **Scenarios Covered**:
  - ✅ Successful login with valid credentials
  - ❌ Invalid email/password combinations
  - ❌ Validation errors (missing fields, invalid formats)
  - 🔒 Security tests (SQL injection, XSS, long inputs)
  - 🔄 Token management (deletion of old tokens)
  - ⚡ Performance and concurrency tests
  - 🌐 Unicode and special character handling

### 2. Logout Endpoint (`/api/auth/logout`) - 15 Test Cases
- **File**: `tests/Feature/AuthLogoutTest.php`
- **Scenarios Covered**:
  - ✅ Successful logout with valid token
  - ❌ Logout without authentication
  - ❌ Invalid/expired/revoked token handling
  - 🔒 Authorization header validation
  - 🔄 Token cleanup verification
  - 📝 HTTP method validation

### 3. User Profile Endpoint (`/api/auth/me`) - 20 Test Cases
- **File**: `tests/Feature/AuthMeTest.php`
- **Scenarios Covered**:
  - ✅ Successful profile retrieval
  - ❌ Unauthenticated access attempts
  - 🔒 Sensitive data exclusion (password, tokens)
  - 🔄 Real-time data updates
  - ⚡ Performance testing
  - 📝 HTTP method validation
  - 🌐 Special character handling

### 4. Forgot Password Endpoint (`/api/auth/forgot-password`) - 25 Test Cases
- **File**: `tests/Feature/AuthForgotPasswordTest.php`
- **Scenarios Covered**:
  - ✅ Successful OTP generation and email sending
  - ❌ Non-existent email handling
  - ❌ Email format validation
  - 📧 Mail service integration testing
  - 🔢 OTP format and expiration validation
  - 🔒 Security testing (injection attempts)
  - ⚡ Rate limiting and performance tests

### 5. OTP Verification Endpoint (`/api/auth/verify-otp`) - 25 Test Cases
- **File**: `tests/Feature/AuthVerifyOTPTest.php`
- **Scenarios Covered**:
  - ✅ Valid OTP verification
  - ❌ Invalid/expired OTP handling
  - ❌ Input validation (email format, OTP length)
  - 🔒 Brute force protection testing
  - 🔄 OTP state management
  - ⚡ Performance and timing tests
  - 🌐 Unicode support

### 6. Password Reset Endpoint (`/api/auth/reset-password`) - 25 Test Cases
- **File**: `tests/Feature/AuthResetPasswordTest.php`
- **Scenarios Covered**:
  - ✅ Successful password reset
  - ❌ Missing/invalid OTP verification
  - ❌ Password validation (length, confirmation)
  - 🔒 Token revocation after reset
  - 🔄 Cleanup of reset records
  - 🔒 Security testing with various password types
  - ⚡ Concurrency and performance tests

## Running the Tests

### Prerequisites
1. Ensure your Laravel environment is set up correctly
2. Configure your testing database (SQLite in-memory is configured by default)
3. Install dependencies: `composer install`

### Run All Authentication Tests
```bash
# Run all authentication tests
php artisan test --filter Auth

# Run with coverage (if XDebug is enabled)
php artisan test --filter Auth --coverage

# Run specific test file
php artisan test tests/Feature/AuthLoginTest.php

# Run specific test method
php artisan test --filter test_successfully_login_with_valid_credentials
```

### Run Tests by Endpoint
```bash
# Login tests only
php artisan test tests/Feature/AuthLoginTest.php

# Logout tests only
php artisan test tests/Feature/AuthLogoutTest.php

# Profile tests only
php artisan test tests/Feature/AuthMeTest.php

# Forgot password tests only
php artisan test tests/Feature/AuthForgotPasswordTest.php

# OTP verification tests only
php artisan test tests/Feature/AuthVerifyOTPTest.php

# Password reset tests only
php artisan test tests/Feature/AuthResetPasswordTest.php
```

### Verbose Output
```bash
# Get detailed test output
php artisan test --filter Auth --verbose

# Stop on first failure
php artisan test --filter Auth --stop-on-failure
```

## Test Categories

### ✅ Success Scenarios (Happy Path)
- Valid credentials and proper authentication flow
- Successful operations with correct data
- Expected behavior under normal conditions

### ❌ Validation and Error Handling
- Missing required fields
- Invalid data formats
- Business logic violations
- Proper error message testing

### 🔒 Security Testing
- SQL injection attempts
- XSS prevention
- Input sanitization
- Authorization checks
- Token security

### 🔄 State Management
- Token lifecycle management
- OTP state transitions
- Data consistency checks
- Cleanup operations

### ⚡ Performance and Load Testing
- Response time validation
- Concurrent request handling
- Rate limiting verification
- Database query optimization

### 📝 API Contract Testing
- HTTP method validation
- Response structure validation
- Status code verification
- Header handling

### 🌐 Internationalization and Edge Cases
- Unicode character support
- Special character handling
- Boundary value testing
- Edge case scenarios

## Test Data Management

### User Factory
- Creates test users with proper attributes
- Supports custom user creation for specific test scenarios
- Handles password hashing automatically

### Password Reset Factory
- Creates OTP records for testing
- Supports verified/unverified states
- Handles expiration scenarios

### Test Helpers
- `createAuthenticatedUser()`: Creates user with valid token
- `authenticatedJson()`: Makes authenticated API requests
- `createOtpRecord()`: Creates OTP records for testing

## Continuous Integration

These tests are designed to run in CI/CD pipelines with:
- Fast execution (in-memory SQLite database)
- No external dependencies
- Comprehensive coverage
- Clear failure reporting

## Test Maintenance

### Adding New Tests
1. Follow the existing naming convention
2. Include proper documentation
3. Cover both positive and negative scenarios
4. Add appropriate assertions

### Updating Tests
1. Keep tests independent and isolated
2. Use factories for test data creation
3. Mock external services (mail, etc.)
4. Maintain test performance

## Expected Results

When all tests pass, you can be confident that:
- ✅ All authentication endpoints work correctly
- ✅ Security measures are properly implemented
- ✅ Input validation is comprehensive
- ✅ Error handling is consistent
- ✅ Performance requirements are met
- ✅ API contracts are maintained