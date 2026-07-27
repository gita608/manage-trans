<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>Privacy Policy | {{ getSetting('app_name', config('app.name')) }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Privacy Policy" name="description" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ brandingUrl('favicon', 'assets/images/favicon.ico') }}">

    @include('partials.pwa-head')

    <!-- Dark Mode Persistence Fix - MUST load before layout.js -->
    <script src="{{ asset('assets/js/dark-mode-fix.js') }}"></script>
    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Dark Mode Custom Styles -->
    <link href="{{ asset('assets/css/dark-mode-custom.css') }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #64748b;
            --border-color: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border-color: rgba(99, 102, 241, 0.2);
            --card-bg: rgba(15, 23, 42, 0.8);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            transition: background 0.3s ease;
            color: var(--text-primary);
        }

        .grid-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            z-index: 0;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .public-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .public-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-lg);
        }

        .public-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .public-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .public-logo img {
            height: 40px;
            width: auto;
        }

        .public-nav {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .public-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .public-nav a:hover {
            color: #6366f1;
        }

        .public-content {
            flex: 1;
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
            width: 100%;
        }

        .public-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-xl);
            padding: 60px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .public-card h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .public-card .last-updated {
            color: var(--text-tertiary);
            font-size: 14px;
            margin-bottom: 40px;
        }

        .public-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-top: 40px;
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        .public-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-top: 32px;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .public-card p {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .public-card ul, .public-card ol {
            margin-left: 24px;
            margin-bottom: 20px;
            color: var(--text-secondary);
        }

        .public-card li {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 12px;
        }

        .public-footer {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-color);
            padding: 40px 20px;
            text-align: center;
            color: var(--text-tertiary);
            font-size: 14px;
        }

        .public-footer a {
            transition: color 0.2s ease;
        }

        .public-footer a:hover {
            color: #6366f1;
        }

        .public-footer a[title]:hover {
            color: #6366f1;
            transform: scale(1.1);
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            color: white;
        }

        @media (max-width: 768px) {
            .public-card {
                padding: 40px 30px;
            }

            .public-card h1 {
                font-size: 32px;
            }

            .public-card h2 {
                font-size: 24px;
            }

            .public-header-content {
                flex-direction: column;
                gap: 16px;
            }

            .public-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="grid-background"></div>

    <div class="public-wrapper">
        <header class="public-header">
            <div class="public-header-content">
                <a href="{{ url('/') }}" class="public-logo">
                    <img src="{{ brandingUrl('app_logo', 'assets/images/logo-light.png') }}" alt="{{ getSetting('app_name', config('app.name')) }}">
                    <span style="font-weight: 700; font-size: 18px;">{{ getSetting('app_name', config('app.name')) }}</span>
                </a>
                <nav class="public-nav">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ route('contact-us') }}">Contact Us</a>
                </nav>
            </div>
        </header>

        <main class="public-content">
            <div class="public-card">
                <h1>Privacy Policy</h1>
                <p class="last-updated">Last updated: {{ date('F d, Y') }}</p>

                <p>
                    At {{ getSetting('app_name', 'MilestoMemories') }}, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our services, including tours and travel, logistics and general transport, visa processing, legal services, and other related services.
                </p>

                <h2>1. Information We Collect</h2>
                
                <h3>1.1 Personal Information</h3>
                <p>We may collect personal information that you provide to us, including but not limited to:</p>
                <ul>
                    <li>Name and contact information (email address, phone number)</li>
                    <li>Account credentials (username, password)</li>
                    <li>Travel documents and visa information</li>
                    <li>Driver license information (for transport services)</li>
                    <li>Vehicle information (for logistics and transport services)</li>
                    <li>Location data (when using our services)</li>
                    <li>Payment information (processed securely through third-party payment processors)</li>
                </ul>

                <h3>1.2 Usage Information</h3>
                <p>We automatically collect certain information when you use our services, including:</p>
                <ul>
                    <li>Device information (IP address, browser type, operating system)</li>
                    <li>Usage patterns and preferences</li>
                    <li>Log files and analytics data</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <p>We use the information we collect for the following purposes:</p>
                <ul>
                    <li>To provide and maintain our services including tours, travel, logistics, transport, visa processing, and legal services</li>
                    <li>To process and manage trip assignments, bookings, and reservations</li>
                    <li>To handle visa applications and related documentation</li>
                    <li>To provide legal services including certificate attestation, notary assistance, and legal consultancy</li>
                    <li>To communicate with you about your account, bookings, and our services</li>
                    <li>To improve and optimize our services</li>
                    <li>To comply with legal obligations and regulatory requirements</li>
                    <li>To detect and prevent fraud or abuse</li>
                </ul>

                <h2>3. Information Sharing and Disclosure</h2>
                <p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                <ul>
                    <li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our platform</li>
                    <li><strong>Legal Requirements:</strong> We may disclose information if required by law or to protect our rights and safety</li>
                    <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred</li>
                </ul>

                <h2>4. Data Security</h2>
                <p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>

                <h2>5. Your Rights</h2>
                <p>You have the right to:</p>
                <ul>
                    <li>Access and receive a copy of your personal information</li>
                    <li>Request correction of inaccurate or incomplete information</li>
                    <li>Request deletion of your personal information</li>
                    <li>Object to processing of your personal information</li>
                    <li>Request restriction of processing</li>
                    <li>Data portability</li>
                </ul>

                <h2>6. Cookies and Tracking Technologies</h2>
                <p>We use cookies and similar tracking technologies to track activity on our platform and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

                <h2>7. Data Retention</h2>
                <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required or permitted by law.</p>

                <h2>8. Children's Privacy</h2>
                <p>Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children.</p>

                <h2>9. Changes to This Privacy Policy</h2>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>

                <h2>10. Contact Us</h2>
                <p>If you have any questions about this Privacy Policy, please contact us:</p>
                <ul>
                    <li>Email: <a href="mailto:info@m2mservicesuae.com" style="color: #6366f1;">info@m2mservicesuae.com</a></li>
                    <li>Phone: <a href="tel:+971565059598" style="color: #6366f1;">+971 56 505 9598</a> or <a href="tel:026358789" style="color: #6366f1;">026358789</a></li>
                    <li>Address: Office no 6, 18th floor Al ferdous tower, Salam Street, Abu Dhabi, UAE</li>
                    <li>Visit our <a href="{{ route('contact-us') }}" style="color: #6366f1;">Contact Us</a> page</li>
                </ul>

                <div style="margin-top: 40px; text-align: center;">
                    <a href="{{ url('/') }}" class="btn-home">
                        <i class="ri-home-line"></i> Back to Home
                    </a>
                </div>
            </div>
        </main>

        <footer class="public-footer">
            <div style="margin-bottom: 20px;">
                <p style="margin-bottom: 16px;">&copy; {{ date('Y') }} {{ getSetting('app_name', 'MilestoMemories') }}. All rights reserved.</p>
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 16px;">
                    <a href="https://www.instagram.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Instagram">
                        <i class="ri-instagram-line"></i>
                    </a>
                    <a href="https://www.facebook.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Facebook">
                        <i class="ri-facebook-line"></i>
                    </a>
                    <a href="https://www.twitter.com/m2mservicesuae" target="_blank" style="color: var(--text-tertiary); font-size: 24px; text-decoration: none; transition: color 0.2s ease;" title="Twitter">
                        <i class="ri-twitter-line"></i>
                    </a>
                </div>
                <p>
                    <a href="{{ route('privacy-policy') }}" style="color: #6366f1; text-decoration: none;">Privacy Policy</a> | 
                    <a href="{{ route('contact-us') }}" style="color: #6366f1; text-decoration: none;">Contact Us</a>
                </p>
            </div>
        </footer>
    </div>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    <script src="{{ asset('assets/js/app-compat.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @include('partials.pwa-scripts')

</body>

</html>
