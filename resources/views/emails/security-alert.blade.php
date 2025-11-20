<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert - New Device Login</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
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
        .alert-box {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            border-left: 5px solid #dc3545;
        }
        .alert-title {
            color: #721c24;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .alert-title::before {
            content: "🚨";
            margin-right: 10px;
            font-size: 20px;
        }
        .alert-text {
            color: #721c24;
            margin: 10px 0;
            font-size: 16px;
        }
        .device-info {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
        }
        .device-section {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .device-section-title {
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .device-item {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .device-label {
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
        }
        .device-value {
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
        }
        .login-time {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #ffc107;
        }
        .login-time-title {
            color: #856404;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .login-time-title::before {
            content: "🕐";
            margin-right: 10px;
        }
        .login-time-text {
            color: #856404;
            font-size: 15px;
            font-weight: bold;
        }
        .security-actions {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            border-left: 5px solid #17a2b8;
        }
        .security-actions-title {
            color: #0c5460;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .security-actions-title::before {
            content: "🔒";
            margin-right: 10px;
        }
        .action-item {
            color: #0c5460;
            margin: 10px 0;
            padding-left: 20px;
            font-size: 15px;
        }
        .action-item::before {
            content: "•";
            color: #17a2b8;
            font-weight: bold;
            margin-right: 10px;
            margin-left: -20px;
        }
        .not-you-box {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .not-you-title {
            color: #721c24;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .not-you-text {
            color: #721c24;
            font-size: 16px;
            margin: 10px 0;
        }
        .contact-button {
            background-color: #dc3545;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
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
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .comparison-table th, .comparison-table td {
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px;
            text-align: left;
        }
        .comparison-table th {
            background-color: rgba(0, 0, 0, 0.1);
            color: #ffffff;
            font-weight: bold;
        }
        .comparison-table td {
            color: #ffffff;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <span class="icon">🛡️</span>
            <h1>Security Alert</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $userName }}</strong>,
            </div>
            
            <div class="alert-box">
                <div class="alert-title">New Device Login Detected</div>
                <div class="alert-text">
                    We detected a login to your account from a device or browser that we haven't seen before.
                    If this was you, you can safely ignore this message.
                </div>
            </div>

            <div class="login-time">
                <div class="login-time-title">Login Time</div>
                <div class="login-time-text">{{ $loginTime }}</div>
            </div>
            
            <div class="device-info">
                <div class="device-section">
                    <div class="device-section-title">New Login Details</div>
                    <table class="comparison-table">
                        <tr>
                            <th>Detail</th>
                            <th>Information</th>
                        </tr>
                        <tr>
                            <td>Browser</td>
                            <td>{{ $currentBrowser }}</td>
                        </tr>
                        <tr>
                            <td>Device</td>
                            <td>{{ $currentDevice }}</td>
                        </tr>
                        <tr>
                            <td>Platform</td>
                            <td>{{ $currentPlatform }}</td>
                        </tr>
                        <tr>
                            <td>IP Address</td>
                            <td>{{ $currentIp }}</td>
                        </tr>
                    </table>
                </div>

                @if($lastBrowser || $lastDevice || $lastPlatform)
                <div class="device-section">
                    <div class="device-section-title">Previous Login Details</div>
                    <table class="comparison-table">
                        <tr>
                            <th>Detail</th>
                            <th>Information</th>
                        </tr>
                        <tr>
                            <td>Browser</td>
                            <td>{{ $lastBrowser ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Device</td>
                            <td>{{ $lastDevice ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Platform</td>
                            <td>{{ $lastPlatform ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>IP Address</td>
                            <td>{{ $lastIp ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                @endif
            </div>
            
            <div class="security-actions">
                <div class="security-actions-title">Recommended Security Actions</div>
                <div class="action-item">Change your password if you suspect unauthorized access</div>
                <div class="action-item">Review your recent login activity</div>
                <div class="action-item">Enable two-factor authentication if not already active</div>
                <div class="action-item">Log out of all devices you don't recognize</div>
                <div class="action-item">Update your browser and operating system</div>
            </div>
            
            <div class="not-you-box">
                <div class="not-you-title">This wasn't you?</div>
                <div class="not-you-text">
                    If you didn't login from this device, your account may be compromised.
                    Please contact our security team immediately.
                </div>
                <a href="mailto:security@company.com" class="contact-button">Report Unauthorized Access</a>
            </div>
            
            <p>This is an automated security notification to keep your account safe. If you have any questions, please contact our support team.</p>
        </div>
        
        <div class="footer">
            <div class="footer-text">
                <strong>Need Help?</strong><br>
                Contact our support team at <a href="mailto:support@company.com" class="contact-info">support@company.com</a><br>
                Security team: <a href="mailto:security@company.com" class="contact-info">security@company.com</a>
            </div>
            <div class="footer-text">
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>