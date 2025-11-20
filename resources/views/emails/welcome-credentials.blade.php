<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the System - Account Created</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #555;
        }
        .credentials-container {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .credentials-label {
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .credentials-info {
            background-color: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
        }
        .credential-item {
            margin: 15px 0;
        }
        .credential-label {
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .credential-value {
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            border-left: 5px solid #ffc107;
        }
        .warning-title {
            color: #856404;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .warning-title::before {
            content: "⚠️";
            margin-right: 10px;
            font-size: 20px;
        }
        .warning-text {
            color: #856404;
            margin: 10px 0;
            font-size: 16px;
        }
        .instructions {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #28a745;
        }
        .instructions-title {
            color: #155724;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .instructions-title::before {
            content: "📋";
            margin-right: 10px;
            font-size: 20px;
        }
        .instruction-step {
            color: #155724;
            margin: 10px 0;
            padding-left: 20px;
            font-size: 15px;
        }
        .instruction-step::before {
            content: "•";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
            margin-left: -20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            color: #6c757d;
            font-size: 14px;
            margin: 5px 0;
        }
        .contact-info {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .security-note {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 5px solid #dc3545;
        }
        .security-note-title {
            color: #721c24;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .security-note-title::before {
            content: "🔒";
            margin-right: 10px;
        }
        .security-text {
            color: #721c24;
            font-size: 14px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <span class="icon">👋</span>
            <h1>Welcome to Our System!</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $userName }}</strong>,
            </div>
            
            <p>Your account has been successfully created in our system. Below are your login credentials:</p>
            
            <div class="credentials-container">
                <div class="credentials-label">Your Login Credentials</div>
                <div class="credentials-info">
                    <div class="credential-item">
                        <div class="credential-label">Email Address:</div>
                        <div class="credential-value">{{ $userEmail }}</div>
                    </div>
                    <div class="credential-item">
                        <div class="credential-label">Temporary Password:</div>
                        <div class="credential-value">{{ $temporaryPassword }}</div>
                    </div>
                </div>
            </div>
            
            <div class="warning-box">
                <div class="warning-title">Important Security Notice</div>
                <div class="warning-text">
                    For your security, you <strong>must change this password</strong> during your first login. 
                    This temporary password should not be used beyond your initial access.
                </div>
            </div>
            
            <div class="instructions">
                <div class="instructions-title">First Login Instructions</div>
                <div class="instruction-step">Go to the login page and enter your email and temporary password</div>
                <div class="instruction-step">After successful login, you will be required to create a new password</div>
                <div class="instruction-step">Choose a strong password with at least 8 characters</div>
                <div class="instruction-step">Confirm your new password and save changes</div>
                <div class="instruction-step">Your account will then be fully activated and ready to use</div>
            </div>
            
            <div class="security-note">
                <div class="security-note-title">Password Security Tips</div>
                <div class="security-text">• Use a combination of uppercase and lowercase letters</div>
                <div class="security-text">• Include numbers and special characters</div>
                <div class="security-text">• Avoid using personal information like names or birthdays</div>
                <div class="security-text">• Never share your password with anyone</div>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        </div>
        
        <div class="footer">
            <div class="footer-text">
                <strong>Need Help?</strong><br>
                Contact our support team at <a href="mailto:support@company.com" class="contact-info">support@company.com</a>
            </div>
            <div class="footer-text">
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>