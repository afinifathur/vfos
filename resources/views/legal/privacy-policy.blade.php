@extends('layouts.legal')

@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy Policy for vFOS (Financial Operating System), detailing data collection, financial ledger privacy, and compliance with the Google API Services User Data Policy.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Document Header -->
    <div class="mb-10 text-center sm:text-left">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-4">
            <span class="material-symbols-outlined text-sm">verified_user</span>
            <span>Official Policy &middot; Google OAuth Compliant</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Privacy Policy</h1>
        <p class="text-slate-400 text-sm sm:text-base mt-2">
            Last Updated: <span class="text-slate-200 font-medium">November 6, 2026</span> &middot; Effective Immediately
        </p>
        <p class="text-xs text-slate-500 mt-1">
            Application: <span class="text-slate-300">vFOS (Financial Operating System)</span> &middot; Production URL: <a href="https://vfos.ngopikopi.my.id" class="text-blue-400 hover:underline">https://vfos.ngopikopi.my.id</a>
        </p>
    </div>

    <!-- Quick Navigation / Table of Contents -->
    <div class="legal-card rounded-2xl p-6 mb-10 shadow-lg">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-blue-400">list_alt</span>
            <span>Table of Contents</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-slate-300">
            <a href="#section-1" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">1.</span> Introduction and Scope
            </a>
            <a href="#section-2" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">2.</span> Information We Collect
            </a>
            <a href="#section-3" class="hover:text-blue-400 text-blue-400 font-semibold transition-colors flex items-center gap-2">
                <span class="text-xs text-blue-500">3.</span> Google API Services & Limited Use
            </a>
            <a href="#section-4" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">4.</span> How We Use Your Information
            </a>
            <a href="#section-5" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">5.</span> Data Storage, Encryption & Security
            </a>
            <a href="#section-6" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">6.</span> Data Retention & Deletion
            </a>
            <a href="#section-7" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">7.</span> Information Sharing & Third Parties
            </a>
            <a href="#section-8" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">8.</span> User Rights and Controls
            </a>
            <a href="#section-9" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">9.</span> Cookies and Tracking
            </a>
            <a href="#section-10" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">10.</span> Changes to this Policy
            </a>
            <a href="#section-11" class="hover:text-blue-400 transition-colors flex items-center gap-2 sm:col-span-2">
                <span class="text-xs text-slate-500">11.</span> Contact Information
            </a>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="space-y-12 text-slate-300 leading-relaxed text-sm sm:text-base">

        <!-- 1. Introduction and Scope -->
        <section id="section-1" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">1</span>
                <span>Introduction and Scope</span>
            </h2>
            <div class="space-y-3 text-slate-300">
                <p>
                    Welcome to <strong class="text-white">vFOS</strong> (&ldquo;Financial Operating System&rdquo;, &ldquo;we&rdquo;, &ldquo;our&rdquo;, or &ldquo;the Application&rdquo;), hosted at <a href="https://vfos.ngopikopi.my.id" class="text-blue-400 hover:underline">https://vfos.ngopikopi.my.id</a>.
                </p>
                <p>
                    vFOS is a private, self-hosted personal finance management system designed to assist users in tracking their personal wealth, bank and cash balances, budget allocations, asset portfolios, liabilities, and daily expenses.
                </p>
                <p>
                    We recognize that personal financial information is exceptionally sensitive. This Privacy Policy details our strict commitments to transparency, privacy, and security regarding what information is collected, how it is stored, how third-party services (such as Google OAuth and the Gmail API) are utilized, and what rights you retain over your data.
                </p>
            </div>
        </section>

        <!-- 2. Information We Collect -->
        <section id="section-2" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">2</span>
                <span>Information We Collect</span>
            </h2>
            <div class="space-y-4">
                <p>We collect only the minimum necessary information required to operate a functional personal finance ledger:</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="legal-card rounded-xl p-5 border border-slate-800">
                        <div class="flex items-center gap-2 text-white font-semibold mb-2">
                            <span class="material-symbols-outlined text-blue-400 text-lg">badge</span>
                            <span>Account Information</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400">
                            User name, email address, and encrypted password credentials utilized to authenticate your account session.
                        </p>
                    </div>

                    <div class="legal-card rounded-xl p-5 border border-slate-800">
                        <div class="flex items-center gap-2 text-white font-semibold mb-2">
                            <span class="material-symbols-outlined text-blue-400 text-lg">account_balance</span>
                            <span>User-Entered Financial Data</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400">
                            Bank, cash, and e-wallet account names, manual transactions, categories, budgets, assets, receivables, and debts entered directly by the user.
                        </p>
                    </div>

                    <div class="legal-card rounded-xl p-5 border border-slate-800">
                        <div class="flex items-center gap-2 text-white font-semibold mb-2">
                            <span class="material-symbols-outlined text-blue-400 text-lg">manage_accounts</span>
                            <span>Google Account Profile</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400">
                            When you connect via Google OAuth 2.0, we receive your Google email address, account name, and profile photo URL to display your connection status.
                        </p>
                    </div>

                    <div class="legal-card rounded-xl p-5 border border-slate-800">
                        <div class="flex items-center gap-2 text-white font-semibold mb-2">
                            <span class="material-symbols-outlined text-blue-400 text-lg">receipt_long</span>
                            <span>Filtered Financial Email Data</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400">
                            With explicit OAuth authorization, vFOS reads bank mutation and transaction notification emails (e.g., sender, date, amount, description) to automate ledger entries.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Google API Services Usage & Limited Use Policy -->
        <section id="section-3" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">3</span>
                <span>Google API Services Usage &amp; Limited Use Disclosure</span>
            </h2>

            <div class="space-y-4">
                <p>
                    vFOS provides an optional integration with Google APIs via OAuth 2.0. This integration is designed specifically to automate personal expense and income tracking by inspecting transaction receipts sent by your financial institutions.
                </p>

                <!-- Prominent Limited Use Callout Box (Google Verification Requirement) -->
                <div class="rounded-2xl p-6 bg-gradient-to-br from-blue-950/40 via-slate-900 to-indigo-950/40 border-2 border-blue-500/40 shadow-xl my-6">
                    <div class="flex items-start gap-4">
                        <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 shrink-0">
                            <span class="material-symbols-outlined text-2xl">policy</span>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-base sm:text-lg font-bold text-white">Google API Services User Data Policy Compliance</h3>
                            <p class="text-sm sm:text-base text-slate-200 font-medium leading-relaxed italic">
                                &ldquo;vFOS&rsquo;s use and transfer to any other app of information received from Google APIs will adhere to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline hover:text-blue-300">Google API Services User Data Policy</a>, including the Limited Use requirements.&rdquo;
                            </p>
                        </div>
                    </div>
                </div>

                <h3 class="text-base font-semibold text-white mt-6 mb-2">OAuth 2.0 Scopes Requested</h3>
                <p class="text-sm text-slate-400">
                    vFOS requests only the minimum necessary scopes required to enable banking notification parsing:
                </p>

                <div class="overflow-x-auto my-4">
                    <table class="w-full text-left text-xs sm:text-sm border border-slate-800 rounded-xl overflow-hidden">
                        <thead class="bg-slate-900 text-slate-300 uppercase tracking-wider text-[11px] border-b border-slate-800">
                            <tr>
                                <th class="p-3">Scope URI</th>
                                <th class="p-3">Purpose &amp; Rationale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80 bg-[#121a29]">
                            <tr>
                                <td class="p-3 font-mono text-blue-400 text-xs">https://www.googleapis.com/auth/gmail.readonly</td>
                                <td class="p-3 text-slate-300">
                                    Read-only access to query, fetch, and parse transaction notification emails sent by financial institutions (e.g., Bank Central Asia, Bank Mandiri).
                                </td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-blue-400 text-xs">https://www.googleapis.com/auth/userinfo.email</td>
                                <td class="p-3 text-slate-300">
                                    Identify the connected Google email address and display authorization status.
                                </td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-blue-400 text-xs">https://www.googleapis.com/auth/userinfo.profile</td>
                                <td class="p-3 text-slate-300">
                                    Display the connected user name and profile avatar in settings.
                                </td>
                            </tr>
                            <tr>
                                <td class="p-3 font-mono text-blue-400 text-xs">openid</td>
                                <td class="p-3 text-slate-300">
                                    Authenticate user identity via OpenID Connect.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-base font-semibold text-white mt-6 mb-2">Strict Technical Boundaries on Gmail Access</h3>
                <ul class="list-disc list-inside space-y-2 text-sm text-slate-300">
                    <li><strong class="text-slate-100">Sender-Filtered Ingestion:</strong> vFOS queries Gmail specifically for messages originating from verified financial senders (such as official banking notification addresses). It does not search, read, or process personal correspondence, social emails, or marketing messages.</li>
                    <li><strong class="text-slate-100">Read-Only Access:</strong> vFOS requests strictly read-only permission (<code class="text-xs bg-slate-800 px-1.5 py-0.5 rounded text-blue-300">gmail.readonly</code>). The application cannot send, modify, trash, forward, or delete any email.</li>
                    <li><strong class="text-slate-100">No Advertising or Commercial Sale:</strong> Google user data is never sold, leased, or transferred to third parties, advertising platforms, data brokers, or marketing networks.</li>
                    <li><strong class="text-slate-100">No AI/ML Model Training:</strong> Google user data and email contents are never used to train, retrain, fine-tune, or improve generalized artificial intelligence or machine learning models.</li>
                    <li><strong class="text-slate-100">Restricted Human Access:</strong> No human or administrator reads your raw emails, except with your explicit written consent for debugging specific parsing issues or if strictly mandated by law.</li>
                </ul>
            </div>
        </section>

        <!-- 4. How We Use Information -->
        <section id="section-4" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">4</span>
                <span>How We Use Your Information</span>
            </h2>
            <div class="space-y-3">
                <p>We use the data collected strictly for the following functional purposes:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-white font-medium block mb-1">Ledger &amp; Financial Reporting</span>
                        <p class="text-xs text-slate-400">Calculate net worth, account balances, monthly profit/loss statements, and budget tracking.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-white font-medium block mb-1">Automated Transaction Ingestion</span>
                        <p class="text-xs text-slate-400">Parse verified bank notification emails to extract transaction date, amount, recipient, and category for bookkeeping.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-white font-medium block mb-1">Authentication &amp; Security</span>
                        <p class="text-xs text-slate-400">Verify user login credentials and prevent unauthorized access or CSRF attacks.</p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                        <span class="text-white font-medium block mb-1">Auditability &amp; Reconciliation</span>
                        <p class="text-xs text-slate-400">Allow users to review parsed transactions against original notification records for bookkeeping precision.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Data Storage, Encryption, and Security -->
        <section id="section-5" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">5</span>
                <span>Data Storage, Encryption &amp; Security</span>
            </h2>
            <div class="space-y-3">
                <p>We apply defense-in-depth security principles to safeguard your personal financial data:</p>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-400 text-lg mt-0.5">lock</span>
                        <div>
                            <strong class="text-white">Encryption at Rest for OAuth Credentials:</strong>
                            <p class="text-slate-400 text-xs sm:text-sm">
                                All Google OAuth tokens (both access tokens and refresh tokens) are encrypted at rest using industry-standard <strong class="text-slate-300">AES-256-CBC</strong> encryption via Laravel&rsquo;s cryptographic kernel before being saved to the database. Plaintext tokens are never stored in log files or raw database columns.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-400 text-lg mt-0.5">enhanced_encryption</span>
                        <div>
                            <strong class="text-white">Encryption in Transit:</strong>
                            <p class="text-slate-400 text-xs sm:text-sm">
                                All communication between your client device, the vFOS server, and Google API servers occurs over enforced <strong class="text-slate-300">HTTPS / TLS 1.2+</strong> encryption.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-400 text-lg mt-0.5">dns</span>
                        <div>
                            <strong class="text-white">Secure Database Architecture:</strong>
                            <p class="text-slate-400 text-xs sm:text-sm">
                                Financial records and synced inbox messages reside in a hardened MySQL 8 database with isolated environment keys and restricted administrative privileges.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- 6. Data Retention and Deletion -->
        <section id="section-6" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">6</span>
                <span>Data Retention and Deletion</span>
            </h2>
            <div class="space-y-4 text-sm">
                <p>
                    We retain your financial records only for as long as you maintain an account on vFOS. You have complete autonomy to delete your data or revoke third-party access at any time:
                </p>
                <div class="space-y-3">
                    <div class="p-4 rounded-xl legal-card border border-slate-800">
                        <strong class="text-white block mb-1">Disconnecting Google Integration:</strong>
                        <p class="text-slate-400 text-xs sm:text-sm">
                            You may disconnect Google at any moment by navigating to <span class="text-slate-200">Settings &rarr; Google Integration &rarr; Disconnect</span>. This immediately purges all stored OAuth access and refresh tokens from our database and terminates background synchronization.
                        </p>
                    </div>
                    <div class="p-4 rounded-xl legal-card border border-slate-800">
                        <strong class="text-white block mb-1">Revoking Access via Google Account:</strong>
                        <p class="text-slate-400 text-xs sm:text-sm">
                            You can revoke vFOS&rsquo;s authorization directly from your Google Account management portal at any time by visiting:
                            <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline block mt-1">https://myaccount.google.com/permissions</a>
                        </p>
                    </div>
                    <div class="p-4 rounded-xl legal-card border border-slate-800">
                        <strong class="text-white block mb-1">Account &amp; Data Erasure:</strong>
                        <p class="text-slate-400 text-xs sm:text-sm">
                            Users may request full deletion of their account and all associated financial records, transactions, and cached inbox messages by contacting the administrator at <a href="mailto:privacy@ngopikopi.my.id" class="text-blue-400 underline">privacy@ngopikopi.my.id</a>. Data is permanently purged from active databases upon confirmation.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Information Sharing and Third Parties -->
        <section id="section-7" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">7</span>
                <span>Information Sharing &amp; Third Parties</span>
            </h2>
            <div class="space-y-3 text-sm">
                <p>
                    <strong class="text-white">vFOS does not sell, rent, monetize, or disclose your personal or financial data to any third party.</strong>
                </p>
                <p class="text-slate-400">
                    We do not engage in targeted advertising, behavioral remarketing, or third-party data analytics. The only external services interacted with are:
                </p>
                <ul class="list-disc list-inside space-y-1 text-slate-300 text-xs sm:text-sm">
                    <li><strong class="text-slate-200">Google APIs:</strong> Solely for the user-authorized synchronization of bank notification emails as described in Section 3.</li>
                    <li><strong class="text-slate-200">Legal Compliance:</strong> We will only disclose information if strictly required under a valid court order, search warrant, or subpoena issued by a court of competent jurisdiction.</li>
                </ul>
            </div>
        </section>

        <!-- 8. User Rights and Controls -->
        <section id="section-8" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">8</span>
                <span>User Rights and Controls</span>
            </h2>
            <div class="space-y-3 text-sm text-slate-300">
                <p>You maintain full sovereignty over your personal finance records:</p>
                <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm text-slate-400">
                    <li><strong class="text-slate-200">Access and Export:</strong> You can view, inspect, and export your financial transactions, accounts, and statements at any time.</li>
                    <li><strong class="text-slate-200">Rectification:</strong> You can edit or correct any transaction, balance, budget, or account entry.</li>
                    <li><strong class="text-slate-200">Revocation:</strong> You can disconnect and revoke external OAuth authorizations at any moment without affecting existing manual records.</li>
                </ul>
            </div>
        </section>

        <!-- 9. Cookies and Tracking -->
        <section id="section-9" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">9</span>
                <span>Cookies and Tracking Technologies</span>
            </h2>
            <div class="space-y-3 text-sm text-slate-300">
                <p>
                    vFOS uses strictly essential first-party cookies necessary for core system security and session state:
                </p>
                <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-slate-400">
                    <li><strong class="text-slate-200">Session Cookie (<code class="text-blue-300">vfos_session</code>):</strong> Identifies your authenticated user session.</li>
                    <li><strong class="text-slate-200">CSRF Token Cookie (<code class="text-blue-300">XSRF-TOKEN</code>):</strong> Protects your account against Cross-Site Request Forgery attacks.</li>
                </ul>
                <p class="text-xs text-slate-500 mt-2">
                    We do not use advertising cookies, behavioral tracking scripts, or third-party web beacons.
                </p>
            </div>
        </section>

        <!-- 10. Changes to this Policy -->
        <section id="section-10" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">10</span>
                <span>Changes to this Privacy Policy</span>
            </h2>
            <div class="space-y-3 text-sm text-slate-300">
                <p>
                    We may update this Privacy Policy from time to time to reflect modifications in our features, legal standards, or Google API policy guidelines. When changes are published, the revised effective date at the top of this document will be updated. Continued use of vFOS after any changes constitutes acceptance of the updated policy.
                </p>
            </div>
        </section>

        <!-- 11. Contact Information -->
        <section id="section-11" class="scroll-mt-24">
            <div class="legal-card rounded-2xl p-6 sm:p-8 border border-blue-500/30">
                <h2 class="text-xl font-bold text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-400">contact_support</span>
                    <span>11. Contact Information</span>
                </h2>
                <p class="text-sm text-slate-400 mb-4">
                    For questions, inquiries, data deletion requests, or concerns regarding this Privacy Policy and our data handling practices, please contact:
                </p>
                <div class="space-y-2 text-sm text-slate-300">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-semibold w-24">Application:</span>
                        <span class="text-white font-medium">vFOS (Financial Operating System)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-semibold w-24">Domain:</span>
                        <a href="https://vfos.ngopikopi.my.id" class="text-blue-400 hover:underline">https://vfos.ngopikopi.my.id</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-semibold w-24">Email:</span>
                        <a href="mailto:privacy@ngopikopi.my.id" class="text-blue-400 hover:underline">privacy@ngopikopi.my.id</a>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-semibold w-24">Administrator:</span>
                        <a href="mailto:admin@ngopikopi.my.id" class="text-blue-400 hover:underline">admin@ngopikopi.my.id</a>
                    </div>
                </div>
            </div>
        </section>

    </div>

</div>
@endsection

