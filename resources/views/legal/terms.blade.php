@extends('layouts.legal')

@section('title', 'Terms of Service')
@section('meta_description', 'Terms of Service governing the use of vFOS (Financial Operating System), including financial disclaimer, Google API integration terms, and limitation of liability.')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Document Header -->
    <div class="mb-10 text-center sm:text-left">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 mb-4">
            <span class="material-symbols-outlined text-sm">gavel</span>
            <span>Terms of Service &middot; User Agreement</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Terms of Service</h1>
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
                <span class="text-xs text-slate-500">1.</span> Acceptance of Terms
            </a>
            <a href="#section-2" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">2.</span> Description of Service
            </a>
            <a href="#section-3" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">3.</span> User Eligibility &amp; Conduct
            </a>
            <a href="#section-4" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">4.</span> Account Security &amp; Credentials
            </a>
            <a href="#section-5" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">5.</span> Third-Party Services &amp; Google API
            </a>
            <a href="#section-6" class="hover:text-blue-400 text-amber-400 font-semibold transition-colors flex items-center gap-2">
                <span class="text-xs text-amber-500">6.</span> Financial Information Disclaimer
            </a>
            <a href="#section-7" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">7.</span> Intellectual Property Rights
            </a>
            <a href="#section-8" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">8.</span> Limitation of Liability &amp; Warranty
            </a>
            <a href="#section-9" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">9.</span> Termination &amp; Suspension
            </a>
            <a href="#section-10" class="hover:text-blue-400 transition-colors flex items-center gap-2">
                <span class="text-xs text-slate-500">10.</span> Governing Law
            </a>
            <a href="#section-11" class="hover:text-blue-400 transition-colors flex items-center gap-2 sm:col-span-2">
                <span class="text-xs text-slate-500">11.</span> Contact Information
            </a>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="space-y-12 text-slate-300 leading-relaxed text-sm sm:text-base">

        <!-- 1. Acceptance of Terms -->
        <section id="section-1" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">1</span>
                <span>Acceptance of Terms</span>
            </h2>
            <div class="space-y-3">
                <p>
                    By registering for, accessing, or using the <strong class="text-white">vFOS</strong> (&ldquo;Financial Operating System&rdquo;) platform located at <a href="https://vfos.ngopikopi.my.id" class="text-blue-400 hover:underline">https://vfos.ngopikopi.my.id</a>, you agree to be bound by these Terms of Service (&ldquo;Terms&rdquo;) and our <a href="{{ route('legal.privacy') }}" class="text-blue-400 hover:underline font-semibold">Privacy Policy</a>.
                </p>
                <p>
                    If you do not agree to these Terms in their entirety, you must not access or use the application.
                </p>
            </div>
        </section>

        <!-- 2. Description of Service -->
        <section id="section-2" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">2</span>
                <span>Description of Service</span>
            </h2>
            <div class="space-y-3">
                <p>
                    vFOS is a private, self-hosted personal finance tracking system designed to help individuals record, categorize, and organize their personal financial transactions, bank accounts, cash reserves, e-wallets, budgets, and net worth estimations.
                </p>
                <p>
                    vFOS provides automated ingestion features through optional third-party integrations (such as Google OAuth and the Gmail API) to streamline the extraction of transaction receipts from bank notifications.
                </p>
            </div>
        </section>

        <!-- 3. User Eligibility and Conduct -->
        <section id="section-3" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">3</span>
                <span>User Eligibility and Conduct</span>
            </h2>
            <div class="space-y-3">
                <p>By using vFOS, you represent and warrant that:</p>
                <ul class="list-disc list-inside space-y-2 text-slate-300 text-sm">
                    <li>You are at least 18 years of age or the legal age of majority in your jurisdiction.</li>
                    <li>You will use the platform exclusively for lawful personal financial bookkeeping.</li>
                    <li>You will not attempt to breach, bypass, probe, or reverse engineer any security controls, encryption keys, or API integrations of the application.</li>
                    <li>You will not use vFOS to process data from third-party accounts or bank credentials that you are not legally authorized to manage.</li>
                </ul>
            </div>
        </section>

        <!-- 4. Account Security and Credentials -->
        <section id="section-4" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">4</span>
                <span>Account Security and Credentials</span>
            </h2>
            <div class="space-y-3">
                <p>
                    You are responsible for maintaining the confidentiality of your account credentials, login passwords, and session tokens. You agree to notify the administrator immediately at <a href="mailto:admin@ngopikopi.my.id" class="text-blue-400 underline">admin@ngopikopi.my.id</a> upon discovering any unauthorized use or potential security breach of your account.
                </p>
            </div>
        </section>

        <!-- 5. Third-Party Integrations & Google API -->
        <section id="section-5" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">5</span>
                <span>Third-Party Integrations &amp; Google API</span>
            </h2>
            <div class="space-y-3">
                <p>
                    vFOS allows users to connect their Google account via Google OAuth 2.0 to access the Gmail API with read-only permissions (<code class="text-xs bg-slate-800 px-1.5 py-0.5 rounded text-blue-300">gmail.readonly</code>) for automated transaction parsing.
                </p>
                <p>
                    By activating this integration, you explicitly grant vFOS permission to access, fetch, and process financial notification emails in accordance with our <a href="{{ route('legal.privacy') }}" class="text-blue-400 underline">Privacy Policy</a> and Google&rsquo;s policies.
                </p>
                <p class="text-slate-400 text-xs sm:text-sm">
                    Your use of Google services is additionally governed by the <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline">Google Terms of Service</a> and the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline">Google API Services User Data Policy</a>. You may revoke access at any time via vFOS Settings or your <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener noreferrer" class="text-blue-400 underline">Google Account Permissions</a>.
                </p>
            </div>
        </section>

        <!-- 6. Financial Information Disclaimer (Legal Shield) -->
        <section id="section-6" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 text-sm font-bold border border-amber-500/20">6</span>
                <span>Financial Information and No Advice Disclaimer</span>
            </h2>

            <!-- Critical Legal Warning Box -->
            <div class="rounded-2xl p-6 bg-gradient-to-br from-amber-950/30 via-slate-900 to-amber-950/20 border-2 border-amber-500/40 shadow-xl my-4">
                <div class="flex items-start gap-4">
                    <div class="p-2.5 rounded-xl bg-amber-500/20 text-amber-400 shrink-0">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <div class="space-y-3 text-slate-200">
                        <h3 class="text-base sm:text-lg font-bold text-amber-300 uppercase tracking-wider">
                            vFOS Is an Informational Bookkeeping Tool Only
                        </h3>
                        <p class="text-xs sm:text-sm leading-relaxed text-slate-300">
                            <strong>NOT FINANCIAL, INVESTMENT, TAX, OR LEGAL ADVICE:</strong> The content, calculations, reports, net worth summaries, and automated transaction data generated by vFOS are provided strictly for general personal organizational and bookkeeping purposes.
                        </p>
                        <p class="text-xs sm:text-sm leading-relaxed text-slate-300">
                            vFOS is <strong>NOT</strong> a certified financial planner (CFP), investment broker, registered banking institution, tax advisor, or legal attorney. No financial fiduciary duty or client-advisor relationship is established by your use of this software.
                        </p>
                        <p class="text-xs sm:text-sm leading-relaxed text-slate-300">
                            You are solely responsible for verifying the accuracy of transaction amounts, exchange rates, and ledger balances. For any critical financial, tax, or investment decisions, always consult a certified professional financial adviser.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Intellectual Property Rights -->
        <section id="section-7" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">7</span>
                <span>Intellectual Property Rights</span>
            </h2>
            <div class="space-y-3">
                <p>
                    The vFOS software codebase, design architecture, graphics, interface components, and logos are the intellectual property of vFOS and its authors, protected under applicable copyright and software laws.
                </p>
                <p>
                    <strong class="text-white">Your Data:</strong> You retain complete and exclusive ownership of all raw personal financial transactions, balances, notes, and records entered or imported into your account.
                </p>
            </div>
        </section>

        <!-- 8. Limitation of Liability and "As-Is" Warranty -->
        <section id="section-8" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">8</span>
                <span>Limitation of Liability &amp; Warranty Disclaimer</span>
            </h2>
            <div class="space-y-3 text-sm">
                <p class="uppercase font-semibold text-slate-200">
                    &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo; Warranty
                </p>
                <p class="text-slate-400">
                    The service is provided on an &ldquo;AS IS&rdquo; and &ldquo;AS AVAILABLE&rdquo; basis without warranties of any kind, whether express, implied, or statutory, including but not limited to merchantability, fitness for a particular purpose, non-infringement, or error-free continuous availability.
                </p>
                <p class="text-slate-400">
                    To the maximum extent permitted by applicable law, vFOS, its developers, and contributors shall not be liable for any direct, indirect, incidental, special, punitive, or consequential damages resulting from:
                </p>
                <ul class="list-disc list-inside space-y-1 text-slate-400 text-xs sm:text-sm">
                    <li>Errors, bugs, or parsing inaccuracies in automated transaction synchronization.</li>
                    <li>Service outages, third-party API changes (including Google API changes), or data loss.</li>
                    <li>Financial decisions, tax penalties, investment losses, or overdraft fees incurred by the user.</li>
                    <li>Unauthorized access to or alteration of your transmissions or data resulting from compromised user devices.</li>
                </ul>
            </div>
        </section>

        <!-- 9. Termination and Suspension -->
        <section id="section-9" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">9</span>
                <span>Termination &amp; Suspension</span>
            </h2>
            <div class="space-y-3 text-sm text-slate-300">
                <p>
                    You may terminate your agreement with these Terms at any time by ceasing to use vFOS and requesting account and data deletion.
                </p>
                <p>
                    We reserve the right to suspend or terminate user access without prior notice if we reasonably suspect any violation of these Terms, unlawful activity, or attempts to compromise platform infrastructure.
                </p>
            </div>
        </section>

        <!-- 10. Governing Law -->
        <section id="section-10" class="scroll-mt-24">
            <h2 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 text-sm font-bold border border-blue-500/20">10</span>
                <span>Governing Law and Dispute Resolution</span>
            </h2>
            <div class="space-y-3 text-sm text-slate-300">
                <p>
                    These Terms of Service and any disputes arising out of or related to the use of vFOS shall be governed by and construed in accordance with the laws of the <strong class="text-white">Republic of Indonesia</strong>, without regard to its conflict of law principles.
                </p>
            </div>
        </section>

        <!-- 11. Contact Information -->
        <section id="section-11" class="scroll-mt-24">
            <div class="legal-card rounded-2xl p-6 sm:p-8 border border-blue-500/30">
                <h2 class="text-xl font-bold text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-400">contact_mail</span>
                    <span>11. Contact Information</span>
                </h2>
                <p class="text-sm text-slate-400 mb-4">
                    If you have questions, feedback, or legal inquiries regarding these Terms of Service, please reach out to:
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
                        <span class="text-slate-500 font-semibold w-24">Legal:</span>
                        <a href="mailto:legal@ngopikopi.my.id" class="text-blue-400 hover:underline">legal@ngopikopi.my.id</a>
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

