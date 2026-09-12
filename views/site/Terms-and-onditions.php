<?php
use yii\helpers\Html;

$this->title = 'Terms & Conditions | Core Aviation Network';

/**
 * Page-specific styles.
 * The legal content remains unchanged; only the presentation layer is improved.
 */
$this->registerCss(<<<CSS
:root {
    --can-bg: #f4f7fb;
    --can-card: #ffffff;
    --can-text: #152033;
    --can-muted: #64748b;
    --can-border: #dbe5f1;
    --can-primary: #0f4c81;
    --can-primary-dark: #0b375d;
    --can-accent: #38bdf8;
    --can-soft: #eaf6ff;
    --can-shadow: 0 22px 60px rgba(15, 23, 42, 0.12);
}

.can-terms-page {
    min-height: calc(100vh - 80px);
    padding: 36px 18px 56px;
    background:
        radial-gradient(circle at 12% 10%, rgba(56, 189, 248, 0.18), transparent 28%),
        radial-gradient(circle at 88% 8%, rgba(15, 76, 129, 0.14), transparent 30%),
        linear-gradient(180deg, #f8fbff 0%, var(--can-bg) 100%);
}

.can-terms-shell {
    width: min(1120px, 100%);
    margin: 0 auto;
}

.can-terms-hero {
    position: relative;
    overflow: hidden;
    padding: 34px 34px 30px;
    border: 1px solid rgba(219, 229, 241, 0.9);
    border-radius: 9px;
    background:
        linear-gradient(135deg, rgba(15, 76, 129, 0.96), rgba(11, 55, 93, 0.96)),
        linear-gradient(135deg, #0f4c81, #0b375d);
    box-shadow: var(--can-shadow);
    color: #ffffff;
}

.can-terms-hero::after {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    right: -120px;
    top: -130px;
    border-radius: 9%;
    background: rgba(56, 189, 248, 0.20);
}

.can-terms-eyebrow {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 8px 13px;
    margin-bottom: 16px;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.12);
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.can-terms-title {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 0;
    color: #ffffff;
    font-size: clamp(2rem, 4vw, 3.35rem);
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.045em;
}

.can-terms-subtitle {
    position: relative;
    z-index: 1;
    max-width: 760px;
    margin: 16px 0 0;
    color: rgba(255, 255, 255, 0.78);
    font-size: 1.02rem;
    line-height: 1.75;
}

.can-terms-switcher {
    position: sticky;
    top: 78px;
    z-index: 10;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    margin: -24px auto 22px;
    padding: 10px;
    width: fit-content;
    max-width: 100%;
    border: 1px solid rgba(219, 229, 241, 0.96);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.can-tab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 46px;
    padding: 0 20px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--can-muted);
    font-weight: 800;
    letter-spacing: -0.01em;
    transition: all 0.2s ease;
}

.can-tab-btn:hover {
    color: var(--can-primary);
    background: var(--can-soft);
}

.can-tab-btn.active {
    color: #ffffff;
    background: linear-gradient(135deg, var(--can-primary), var(--can-primary-dark));
    box-shadow: 0 10px 22px rgba(15, 76, 129, 0.24);
}

.can-terms-card {
    overflow: hidden;
    border: 1px solid rgba(219, 229, 241, 0.95);
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: var(--can-shadow);
}

.can-terms-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 28px;
    border-bottom: 1px solid var(--can-border);
    background: linear-gradient(180deg, #ffffff, #f8fbff);
}

.can-terms-card-top strong {
    display: block;
    color: var(--can-text);
    font-size: 1rem;
}

.can-terms-card-top span {
    display: block;
    margin-top: 3px;
    color: var(--can-muted);
    font-size: 0.9rem;
}

.can-terms-badge {
    flex: 0 0 auto;
    padding: 8px 12px;
    border-radius: 9px;
    background: #ecfeff;
    color: #036779;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.can-terms-content {
    padding: 30px 34px 38px;
}

.can-terms-pane {
    display: none;
    animation: canFadeIn 0.18s ease-in-out;
}

.can-terms-pane.is-active {
    display: block;
}

.terms-section {
    color: var(--can-text);
}

.terms-section .terms-section {
    margin-top: 26px;
    padding-top: 24px;
    border-top: 1px solid var(--can-border);
}

.terms-section h2,
.terms-section h3,
.terms-section h4 {
    color: #10243e !important;
    letter-spacing: -0.02em;
}

.terms-section h2 {
    margin-top: 0 !important;
    font-size: clamp(1.45rem, 2.4vw, 2rem);
    font-weight: 800;
}

.terms-section h3,
.terms-section h4 {
    margin-top: 30px;
    margin-bottom: 14px !important;
    font-size: 1.12rem;
    font-weight: 800;
}

.terms-section p,
.terms-section li {
    color: #334155;
    font-size: 0.98rem;
}

.terms-section p {
    line-height: 1.78 !important;
}

.terms-section ul {
    padding-left: 1.25rem !important;
}

.terms-section li {
    margin-bottom: 8px;
    line-height: 1.65 !important;
}

.terms-section a {
    color: var(--can-primary);
    font-weight: 700;
    text-decoration: none;
    border-bottom: 1px solid rgba(15, 76, 129, 0.24);
}

.terms-section a:hover {
    color: var(--can-primary-dark);
    border-bottom-color: currentColor;
}

@keyframes canFadeIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .can-terms-page {
        padding: 22px 12px 36px;
    }

    .can-terms-hero {
        padding: 26px 22px;
        border-radius: 22px;
    }

    .can-terms-switcher {
        position: relative;
        top: auto;
        width: 100%;
        margin: 14px 0 18px;
        border-radius: 20px;
    }

    .can-tab-btn {
        flex: 1 1 100%;
        width: 100%;
    }

    .can-terms-card {
        border-radius: 22px;
    }

    .can-terms-card-top {
        flex-direction: column;
        align-items: flex-start;
        padding: 18px 20px;
    }

    .can-terms-content {
        padding: 22px 20px 28px;
    }
}
CSS);
?>

<main class="can-terms-page">
    <section class="can-terms-shell" aria-labelledby="terms-page-title">
        <header class="can-terms-hero">
            <div class="can-terms-eyebrow">✈ Core Aviation Network</div>
            <h1 id="terms-page-title" class="can-terms-title">Terms &amp; Conditions</h1>
            <p class="can-terms-subtitle">
                Review the terms that apply to MRO providers and Aircraft Operators using the Core Aviation Network platform.
            </p>
        </header>

        <!-- Terms switcher -->
        <div class="can-terms-switcher" role="tablist" aria-label="Terms categories">
            <button id="mro-btn" type="button" class="can-tab-btn active" data-target="#mro-terms" role="tab" aria-selected="true" aria-controls="mro-terms">
                MRO Terms &amp; Conditions
            </button>
            <button id="ao-btn" type="button" class="can-tab-btn" data-target="#ao-terms" role="tab" aria-selected="false" aria-controls="ao-terms">
                AO Terms &amp; Conditions
            </button>
        </div>

        <div class="can-terms-card">
            <div class="can-terms-card-top">
                <div>
                    <strong>Legal information</strong>
                    <span>Select the profile type above to read the applicable conditions.</span>
                </div>
                <div class="can-terms-badge">Platform policy</div>
            </div>

            <div class="can-terms-content">
<!-- MRO Terms Section -->
<div id="mro-terms" class="terms-section can-terms-pane is-active" role="tabpanel" aria-labelledby="mro-btn">
    <h2 style="color: #444; margin-bottom: 20px;">MRO Terms & Conditions</h2>
    <p style="line-height: 1.6; margin-bottom: 20px;">This section explains the terms and conditions for MRO services provided by Core Aviation Network.</p>
    
    <!-- Introduction -->
    <h3>1. Introduction</h3>
    <p>1.1 Welcome to Core Aviation Network (also referred to as the "Company"), operating the <a href="https://coreaviationnetwork.com">https://coreaviationnetwork.com</a> Platform ("Platform"). These terms and conditions ("Terms") govern your use of the Platform as an MRO. By registering as an MRO on Core Aviation Network, you agree to comply with and be bound by these Terms. Please read them carefully.</p>
    <p>1.2 The Platform serves as a global online marketplace where Aircraft Operators and Maintenance, Repair and Overhaul (MRO) centres can connect, collaborate, and exchange Purchase Orders (PO), certificates of National Aviation Authority (NAA), and Aircraft Type Approvals. The Company facilitates these interactions, aiming to enhance efficiency and transparency within the aviation industry.</p>
    <p>1.3 By accessing or using the Platform, you acknowledge that you have read, understood, and agree to be bound by these Terms. If you do not agree to these Terms, you may not access or use the Platform.</p>
    <p>1.4 These Terms are supplemented by our Privacy Policy, which outlines how we collect, use, and disclose your information. By using the Platform, you consent to the practices described in the Privacy Policy, which forms part of these Terms.</p>
    <p>1.5 The Company reserves the right to modify, update, or revise these Terms at any time. Changes will be effective upon posting on the Platform. It is your responsibility to review these Terms periodically for updates. Your continued use of the Platform after any such changes constitutes acceptance of the revised Terms.</p>
    
    <!-- Definitions -->
    <h3>2. Definitions</h3>
    <p>2.1 "Company" refers to Core Aviation Network.</p>
    <p>2.2 "Platform" means the online Platform <a href="https://coreaviationnetwork.com">https://coreaviationnetwork.com</a> provided by Core Aviation Network where Aircraft Operators and Maintenance, Repair and Overhaul (MRO) centres can connect and interact.</p>
    <p>2.3 "MRO" refers to any entity or individual registered on the Platform offering services related to Maintenance, Repair, and Overhaul (MRO) within the aviation industry.</p>
    <p>2.4 “Terms” refers to these Terms and Conditions for MROs.</p>
    <p>2.5 "User" refers collectively to MROs and Aircraft Operators using the Platform.</p>

<!-- Registration and Account -->
<div id="mro-registration" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">3. Registration and Account</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        3.1 To access and use the services as a MRO on the Platform, you must register and create an account. By registering, you agree to provide accurate, current, and complete information about yourself or your organisation as prompted by the registration form. You also agree to maintain and promptly update this information to ensure it remains accurate, current, and complete.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        3.2 Upon registration, you will be required to choose a username and password. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Immediately notify Core Aviation Network of any unauthorised use of your account or any other breach of security.</li>
        <li>Ensure that you log out from your account at the end of each session.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        3.3 You acknowledge and agree that the Company may access, preserve, and disclose your account information and content if required to do so by law or in a good faith belief that such access, preservation, or disclosure is reasonably necessary to:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Comply with legal process.</li>
        <li>Enforce these Terms.</li>
        <li>Respond to claims that any content violates the rights of third parties.</li>
        <li>Protect the rights, property, or personal safety of Core Aviation Network, its users, or the public.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        3.4 You may terminate your account at any time by contacting the Company’s customer support. Upon termination, your account will be deactivated, and your access to the Platform will be terminated. However, please note that termination of your account does not relieve you of any obligation to pay any outstanding fees or resolve any outstanding disputes.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        3.5 Core Aviation Network reserves the right to refuse registration of, or cancel an account in its discretion, at any time.
    </p>
</div>

<!-- Services -->
<div id="mro-services" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">4. Services</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.1 The Company facilitates the connection and interaction between Aircraft Operators and Maintenance, Repair and Overhaul (MRO) centres globally. The Platform allows MROs to showcase their capabilities, list services offered, and engage with Aircraft Operators for the exchange of Purchase Orders (PO).
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.2 MROs are responsible for:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Maintaining accurate and up-to-date information on their profiles, including details of services offered, certifications, and qualifications.</li>
        <li>Responding promptly and professionally to Purchase Orders and requests for information from Aircraft Operators.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.3 Any agreements for services entered into between Aircraft Operators and MROs are solely between those parties. Core Aviation Network is not a party to such agreements and shall not be liable for any disputes, claims, or damages arising from or related to such agreements.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.4 The Platform may include features that enable MROs to communicate directly with Aircraft Operators, manage transactions, and track the status of orders and approvals.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.5 MROs acknowledge and agree that Core Aviation Network does not endorse, guarantee, or verify the accuracy, completeness, or legality of any information provided by Aircraft Operators or other Users on the Platform. MROs are solely responsible for assessing the suitability and reliability of interactions and transactions with Aircraft Operators.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        4.6 Core Aviation Network reserves the right to introduce, modify, or discontinue features of the Platform at any time without notice. Such changes may affect the availability or functionality of certain services offered through the Platform.
    </p>
</div>

    
<!-- Obligations of MROs -->
<div id="mro-obligations" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">5. Obligations of MROs</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.1 MROs warrant that all information provided on their profiles, including but not limited to services offered, certifications, qualifications, contact details, and business information, is accurate, current, and complete. MROs undertake to promptly update any changes to this information to maintain its accuracy.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.2 MROs shall comply with all applicable laws, regulations, and industry standards governing their operations, including but not limited to aviation safety regulations, environmental laws, and data protection regulations. MROs shall ensure that their services and operations conform to these requirements at all times.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.3 MROs agree to conduct themselves in a professional manner when interacting with Aircraft Operators and other Users of the Platform. This includes responding promptly to Purchase Orders, requests for information, and communications from Aircraft Operators, and maintaining open and respectful communication throughout transactions.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.4 MROs shall maintain high standards of service quality, safety, and reliability in the delivery of their services. This includes adhering to industry best practices, complying with relevant quality management standards (e.g., ISO standards), and ensuring that all services provided meet or exceed contractual obligations and customer expectations.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.5 MROs shall maintain accurate and comprehensive records of all transactions, communications, Purchase Orders, certificates of National Aviation Authority (NAA), Aircraft Type Approvals, and other relevant documentation related to their engagements through the Platform. These records should be retained in accordance with legal and regulatory requirements.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.6 MROs agree to maintain the confidentiality of any proprietary or confidential information disclosed to them by Aircraft Operators or other Users through the Platform. This includes but is not limited to technical specifications, business strategies, and customer data. MROs shall not disclose such information to third parties without proper authorisation, unless required by law.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.7 In the event of disputes or disagreements with Aircraft Operators or other users, MROs agree to engage in good faith efforts to resolve issues amicably and professionally. MROs shall cooperate with GLOBALMROS LTD in any dispute resolution processes facilitated through the Platform, including mediation or arbitration, if necessary.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        5.8 MROs shall conduct their business in an ethical manner, adhering to principles of fairness, integrity, and transparency. This includes refraining from engaging in fraudulent activities, bribery, corruption, or any other unlawful or unethical practices that may damage the reputation of GLOBALMROS LTD or the aviation industry.
    </p>
</div>

<!-- Payments -->
<div id="mro-payments" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">6. Payments</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.1 Core Aviation Network applies a 3% fee of the total value of the invoice to the provider for each request obtained through the Platform. This fee is applicable to all transactions processed on the Platform between MROs and Aircraft Operators.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.2 MROs shall receive payments for services rendered in accordance with agreed terms and conditions between them and Aircraft Operators. Payment terms, including rates, invoicing procedures, and payment schedules, shall be mutually agreed upon between MROs and Aircraft Operators prior to the commencement of services.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.3 Unless otherwise agreed by the parties, all payments made through the Platform shall be in GBP. The currency for payments may be determined by mutual agreement between the parties. MROs are responsible for determining and complying with any applicable taxes, duties, or other governmental levies associated with their transactions and income generated through the Platform.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.4 In the event of disputes regarding payments or services rendered, MROs agree to cooperate with Core Aviation Network and Aircraft Operators in resolving such disputes promptly and in good faith. Refunds, if applicable, shall be processed in accordance with the refund policy specified by Core Aviation Network or as agreed upon between MROs and Aircraft Operators.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.5 Core Aviation Network employs reasonable measures to secure transactions conducted through the Platform. However, MROs are responsible for taking appropriate precautions to protect their financial information and account credentials from unauthorized access or use.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.6 Core Aviation Network reserves the right to modify or update payment terms, including fees and charges, at any time. Changes to payment terms will be communicated to MROs through the Platform or via email. Continued use of the Platform after such changes constitutes acceptance of the modified payment terms.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        6.7 All payments to MROs for Services shall be made and processed through the Core Aviation Network Platform. If the parties do not invoice through our Platform for the initial work or any subsequent works arising from the request or aircraft input, Core Aviation Network shall not intervene in or consider any disputes or resolutions. Furthermore, Core Aviation Network will not be liable for any financial losses incurred by either party related to such work, including but not limited to work obtained through our Platform and any subsequent work arising therefrom. Additionally, Core Aviation Network reserves the right to dismiss and delete any feedback regarding such work. Core Aviation Network will also seek to recuperate the losses and any fees arising for this through legal measures.
    </p>
</div>

<!-- Information on the Website -->
<div id="mro-information" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">7. Information on the Website</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        7.1 Core Aviation Network strives to provide accurate and up-to-date information on the Platform. However, Core Aviation Network does not warrant the accuracy, completeness, or reliability of any information contained on the Platform, including:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Information about Aircraft Operators, their fleet details, or maintenance requirements.</li>
        <li>Content posted by Aircraft Operators on the Platform.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        7.2 Core Aviation Network reserves the right to modify, suspend, or discontinue the Platform or any part of its content at any time without prior notice.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        7.3 The Platform is provided "as is" and "as available" without warranties of any kind, express or implied. Core Aviation Network disclaims all warranties, including, but not limited to, warranties of merchantability, fitness for a particular purpose, and accuracy of information.
    </p>
</div>


<!-- Content and Conduct -->
<div id="mro-content-and-conduct" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">8. Content and Conduct</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.1 MROs are solely responsible for any content they upload, post, or transmit on the Platform, including but not limited to profiles, service listings, descriptions, comments, and communications with Aircraft Operators. MROs warrant that all content provided is accurate and lawful.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.2 MROs agree not to engage in any conduct that:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Violates any applicable laws, regulations, or industry standards.</li>
        <li>Infringes upon the intellectual property rights of others, including copyrights, trademarks, or patents.</li>
        <li>Harasses, abuses, or threatens other Users of the Platform.</li>
        <li>Contains or promotes spam, malware, or other malicious content.</li>
        <li>Interferes with the operation or security of the Platform, or attempts to gain unauthorized access to any systems or data.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.3 Core Aviation Network reserves the right, but has no obligation, to monitor, review, or remove any content uploaded or posted by MROs that violates these Terms or is otherwise objectionable. MROs acknowledge and agree that the Company may remove or disable access to any content without prior notice.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.4 MROs may receive feedback, ratings, and reviews from Aircraft Operators and other Users of the Platform based on their interactions and services provided. MROs agree that feedback and reviews may be publicly displayed on the Platform and shall not be falsified or manipulated in any manner.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.5 MROs agree to comply with all policies, guidelines, and instructions provided by Core Aviation Network regarding acceptable use of the Platform, including but not limited to content standards, privacy policies, and community guidelines.
    </p>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        8.6 In the event of a breach of these content and conduct provisions, Core Aviation Network may take appropriate action, including but not limited to suspending or terminating access to the Platform, removing content, or pursuing legal remedies as necessary.
    </p>
</div>


<!-- Acceptable Use Policy -->
<div id="mro-acceptable-use-policy" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">9. Acceptable Use Policy</h2>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        9.1 MROs agree to use the Platform in a responsible and ethical manner that complies with all applicable laws and regulations, industry standards, and best practices. This includes, but is not limited to:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Maintaining an accurate and up-to-date profile on the Platform, including certifications, capabilities, service offerings, and contact information.</li>
        <li>Responding promptly and professionally to inquiries and requests from Aircraft Operators.</li>
        <li>Complying with all terms and conditions of any Service agreements entered into with Aircraft Operators found on the Platform.</li>
        <li>Not engaging in any activity that could disrupt, damage, or disable the Platform or its functionality.</li>
        <li>Not using the Platform to transmit any illegal, harassing, defamatory, obscene, or threatening content.</li>
        <li>Not providing false or misleading information about your services, capabilities, or experience.</li>
        <li>Not engaging in any unfair or deceptive practices towards Aircraft Operators.</li>
        <li>Not using the Platform to collect or harvest personal data of Aircraft Operators without their consent.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        9.2 Core Aviation Network reserves the right, at its sole discretion, to take action against any MRO who violates these Terms, including:
    </p>
    
    <ul style="line-height: 1.6; margin-bottom: 20px; padding-left: 20px; list-style-type: lower-alpha;">
        <li>Issuing warnings or notifications.</li>
        <li>Hiding or suspending your profile listing on the Platform.</li>
        <li>Restricting your access to certain features or functionalities of the Platform.</li>
        <li>Terminating your account and access to the Platform.</li>
        <li>Taking legal action against you.</li>
    </ul>
    
    <p style="line-height: 1.6; margin-bottom: 20px;">
        9.3 If you encounter any misuse of the Platform by another MRO or any violation of these Terms, please report it to Core Aviation Network immediately through the Platform's designated channels.
    </p>
</div>


<!-- Intellectual Property -->
<div id="mro-intellectual-property" class="terms-section">
    <h2 style="color: #444; margin-bottom: 20px;">10. Intellectual Property</h2>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        10.1 MROs retain ownership of all intellectual property rights in the content they upload, post, or transmit on the Core Aviation Network Platform ("Platform"), including but not limited to profiles, service listings, descriptions, logos, and trademarks ("MRO Content"). By uploading, posting, or transmitting MRO Content on the Platform, MROs grant Core Aviation Network a worldwide, non-exclusive, royalty-free, sublicensable, and transferable licence to use, reproduce, distribute, modify, adapt, display, and publish the MRO Content solely for the purpose of operating and promoting the Platform and its services.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        10.2 MROs agree not to upload, post, or transmit any content on the Platform that infringes upon the intellectual property rights of others, including copyrights, trademarks, patents, trade secrets, or any other proprietary rights. MROs shall promptly remove any infringing content upon notification by Core Aviation Network or the rightful owner of the intellectual property.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        10.3 All intellectual property rights in the Platform, including but not limited to software, algorithms, source code, designs, trademarks, logos, and content provided by Core Aviation Network ("Company Content"), are owned by or licensed to Core Aviation Network. MROs acknowledge and agree that they do not acquire any ownership rights in the Company Content by using the Platform.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        10.4 MROs may access and use the Company Content solely for the purpose of using the Platform in accordance with these Terms. MROs shall not reproduce, distribute, modify, or create derivative works of the Company Content without prior written consent from Core Aviation Network.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        10.5 MROs may choose to provide feedback, suggestions, or other input regarding the Platform to Core Aviation Network ("Feedback"). MROs agree that Core Aviation Network may use and incorporate Feedback without any obligation to MROs, including for improving the Platform or developing new features and services.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;"> 10.6 In the event of any third-party claim that the Platform or its use infringes on any intellectual property rights, Core Aviation Network shall, at its discretion, either (i) modify the Platform to avoid infringement, (ii) obtain the necessary rights for continued use, or (iii) terminate the availability of the infringing content. Core Aviation Network shall not be liable for any claims arising from MRO Content posted by MROs that infringe upon the intellectual property rights of third parties. </p>

</div>


    <!-- Liability -->
    <h3>11. Liability</h3>
    <p>11.1	The Company acts solely as a Platform for connecting MROs and Aircraft Operators. We do not guarantee the accuracy, reliability, or legality of any information provided by Users.</p>
    <p>11.2	To the extent permitted by law, GLOBALMROS LTD shall not be liable to MROs for any indirect, incidental, consequential, punitive, or special losses or damages, including loss of profits, revenue, data, or business opportunities arising out of or in connection with these Terms or use of the Platform, even if advised of the possibility of such damages.</p>
    <p>11.3	GLOBALMROS LTD's total liability to MROs in respect of all claims arising out of or in connection with these Terms or use of the Platform shall be limited to the total amount of fees paid by MROs to GLOBALMROS LTD in the twelve (12) months preceding the event giving rise to the liability.</p>
    <p>11.4	Nothing in these Terms shall limit or exclude liability for death or personal injury resulting from negligence, fraud, fraudulent misrepresentation, or any other liability that cannot be excluded or limited by applicable law.</p>
    <p>11.5	MROs agree to indemnify, defend, and hold harmless GLOBALMROS LTD and its directors, officers, employees, and agents from and against any and all claims, demands, losses, liabilities, damages, costs, and expenses (including legal fees) arising out of or in connection with:
a)	Any breach of these Terms by MROs;
b)	MRO Content or any other content uploaded, posted, or transmitted by MROs on the Platform;
c)	Use of the Platform by MROs;
d)	Violation of any rights of third parties by MROs, including intellectual property rights.
</p>
    <p>11.6	GLOBALMROS LTD shall promptly notify MROs in writing of any claims for which it seeks indemnification. MROs shall have the right to assume the defence and control of any such claim, provided that GLOBALMROS LTD may participate in the defence at its own expense.</p>
    <p>11.7	Any claims under this clause must be notified in writing to the other party within a reasonable time after the claimant becomes aware of the circumstances giving rise to the claim.</p>
    <p>11.8	The provisions of this clause shall survive termination of these Terms or termination of access to the Platform.</p>
 
 
    <h3>12.	Termination</h3>
    <p>12.1	MROs may terminate these Terms and their account on the GLOBALMROS Platform at any time by following the account termination procedures provided on the Platform. Termination will be effective upon completion of the account termination process. MROs shall remain liable for any obligations incurred prior to termination, including payment obligations.</p>
    <p>12.2	GLOBALMROS LTD reserves the right to suspend or terminate MROs' access to the Platform or terminate these Terms at its sole discretion, without cause or prior notice. Reasons for termination may include, but are not limited to:
a)	Breach of these Terms by MROs.
b)	Violation of applicable laws or regulations.
c)	Conduct that GLOBALMROS LTD determines to be harmful to other Users, the Platform, or its reputation.
</p>
    <p>12.3	Upon termination of these Terms, MROs' right to access and use the Platform shall cease immediately. MROs shall immediately cease all use of the Platform and any Company Content.</p>
    <p>12.4	MROs may appeal the decision of termination by contacting GLOBALMROS LTD within 7 days following notification of termination. GLOBALMROS LTD will review the appeal and notify MROs of its decision within a reasonable time.</p>
  
  
    <h3>13.	Miscellaneous</h3>
    <p>13.1	Governing Law: These Terms shall be governed by and construed in accordance with English law. Any disputes arising under these Terms shall be subject to the exclusive jurisdiction of the English courts.</p>
    <p>13.2	Amendments: The Company reserves the right to modify or amend these Terms at any time. Notice of such changes will be communicated to MROs through the Platform.</p>
    <p>13.3	Modifications to Services: The Company reserves the right to modify or discontinue any part of the Platform or services offered therein without notice. MROs acknowledge and agree that the Company shall not be liable to them or any third party for any modification, suspension, or discontinuance of the Platform.</p>
    <p>13.4	Force Majeure: The Company shall not be liable for any failure or delay in performing its obligations under these Terms due to causes beyond its reasonable control, including but not limited to acts of God, war, terrorism, pandemics, strikes, or natural disasters.</p>
    <p>13.5	Entire Agreement: These Terms constitute the entire agreement between the MROs and the Company regarding the use of the Platform, superseding any prior agreements or understandings.</p>
    <p>13.6	Third Party Links: MROs acknowledge that the Platform may contain links to third-party websites or resources. GLOBALMROS LTD is not responsible for the availability, accuracy, or content of such third-party sites and resources, and does not endorse any products, services, or content offered through them.</p>
    <p>13.7	Severability: If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions shall continue to be valid and enforceable to the fullest extent permitted by law.</p>
    <p>13.8	Waiver: The failure of the Company to enforce any right or provision of these Terms shall not constitute a waiver of that right or provision unless acknowledged and agreed to by the Company in writing.</p>
    <!-- Continue for additional clauses -->
</div>
        
<!-- AO Terms Section (Hidden by default) -->
<div id="ao-terms" class="terms-section can-terms-pane" style="display: none;" role="tabpanel" aria-labelledby="ao-btn">
    <h3 style="color: #444; margin-bottom: 20px;">Terms and Conditions for Aircraft Operators</h3>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        These terms and conditions ("Terms") govern your use of <a href="https://globalmros.com" target="_blank">https://globalmros.com</a> (the "Platform") as an Aircraft Operator. The Platform, operated by GLOBALMROS LTD ("GLOBALMROS"), provides a specialised online marketplace designed to connect Aircraft Operators with Maintenance, Repair, and Overhaul service providers ("MROs") worldwide. By accessing and using the Platform, Aircraft Operators can effectively procure essential maintenance, repair, and overhaul services for their aircraft fleets, along with support for Aircraft on Ground (AOG) services.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        GLOBALMROS facilitates seamless transactions and connections between Aircraft Operators and MROs, ensuring transparency, efficiency, and compliance with industry standards. These Terms outline the rights, obligations, and responsibilities of Aircraft Operators when using the Platform to engage with MROs. It is important to review and understand these Terms as they govern your use of the Platform and interactions with MROs.
    </p>
    <p style="line-height: 1.6; margin-bottom: 20px;">
        Please read these Terms carefully. By accessing or using the Platform, you agree to be bound by these Terms. If you do not agree to these Terms, you may not use the Platform. These Terms constitute a legally binding agreement between you and GLOBALMROS regarding your use of the Platform.
    </p>

    <h4 style="color: #444; margin-bottom: 20px;">1. Definitions</h4>
    <p style="line-height: 1.6; margin-bottom: 20px;">In these Terms, the following definitions apply:</p>
    <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        <li><strong>"Aircraft Operator"</strong> means an entity engaged in the operation of aircraft or any buyer of the services offered by MROs in the Platform.</li>
        <li><strong>"GLOBALMROS"</strong> means GLOBALMROS LTD, the company providing the Platform.</li>
        <li><strong>"Platform"</strong> means the online Platform provided by GLOBALMROS for connecting Aircraft Operators with Maintenance, Repair, and Overhaul MROs ("MROs").</li>
        <li><strong>"MROs"</strong> means Maintenance, Repair, and Overhaul service providers and other third-party service providers registered on the Platform.</li>
        <li><strong>“Terms”</strong> means this Terms and Conditions for Aircraft Operators.</li>
    </ul>

    <h4 style="color: #444; margin-bottom: 20px;">2. Registration and Account</h4>
    <p style="line-height: 1.6; margin-bottom: 20px;">2.1 To access and utilise the full features of the Platform, Aircraft Operators must create an account by completing the registration process on the GLOBALMROS website. During registration, Aircraft Operators are required to provide accurate and complete information.</p>
    <p style="line-height: 1.6; margin-bottom: 20px;">2.2 Aircraft Operators are responsible for maintaining the confidentiality of their account credentials and for all activities that occur under their account. You agree to:</p>
    <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        <li>a) Keep your account information accurate and up to date.</li>
        <li>b) Promptly update any changes to your account information.</li>
        <li>c) Safeguard your account credentials and prevent unauthorised access to or use of your account.</li>
    </ul>
    <p style="line-height: 1.6; margin-bottom: 20px;">2.3 GLOBALMROS may require verification of your identity and credentials as an Aircraft Operator to access certain features or services on the Platform. Verification processes may include submitting documentation or undergoing identity verification checks.</p>

    <h4 style="color: #444; margin-bottom: 20px;">3. Services</h4>
    <p style="line-height: 1.6; margin-bottom: 20px;">3.1 The Platform facilitates Aircraft Operators in procuring essential maintenance, repair, overhaul and AOG services ("Services") from MROs. Aircraft Operators may use the Platform to:</p>
    <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        <li>a) Search for qualified MROs based on specific criteria such as location, certifications, and service offerings.</li>
        <li>b) Review MRO profiles, capabilities, and customer feedback to make informed decisions.</li>
        <li>c) Initiate requests for quotations ("RFQs") and negotiate service agreements with MROs.</li>
    </ul>
    <p style="line-height: 1.6; margin-bottom: 20px;">3.2 Any agreements or contracts for Services entered into between Aircraft Operators and MROs are strictly between those parties. GLOBALMROS does not participate in or influence the terms, negotiations, or fulfilment of Service agreements. It is the responsibility of Aircraft Operators to:</p>
    <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; line-height: 1.6;">
        <li>a) Conduct due diligence and verify the qualifications, capabilities, and reputation of MROs before engaging their services.</li>
        <li>b) Negotiate and finalise the terms of Service agreements, including pricing, scope of work, timelines, and any other relevant terms.</li>
    </ul>
    <p style="line-height: 1.6; margin-bottom: 20px;">3.3 While GLOBALMROS strives to maintain a high standard of Service provider listings and performance, Aircraft Operators acknowledge that GLOBALMROS does not guarantee the quality, accuracy, or suitability of Services provided by MROs. Aircraft Operators are encouraged to provide feedback and ratings based on their experiences to help maintain the integrity and reliability of the Platform.</p>
    <p style="line-height: 1.6; margin-bottom: 20px;">3.4 In the event of disputes or disagreements arising from Service agreements between Aircraft Operators and MROs, GLOBALMROS may, at its discretion, provide mediation services to facilitate resolution. We will intervene to facilitate amicable and expeditious resolution of issues once the parties have been afforded an opportunity to address the matter independently, without our involvement. However, GLOBALMROS is not obligated to intervene in disputes and shall not be liable for any claims, losses, or damages arising from such disputes.</p>

    <h4 style="color: #444; margin-bottom: 20px;">4. Obligations of Aircraft Operators</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    4.1 Aircraft Operators shall comply with all applicable laws, regulations, and industry standards relevant to their operations, including but not limited to aviation safety, maintenance, and environmental regulations. This includes obtaining and maintaining all necessary permits, licences, and certifications required for the operation and maintenance of their aircraft fleet.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.2 Aircraft Operators shall ensure that all information provided to GLOBALMROS and MROs through the Platform is accurate, complete, and up to date. This includes aircraft specifications, maintenance histories, and any other relevant operational data necessary for the provision of Services by MROs.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.3 Aircraft Operators shall prioritise the safety and security of their aircraft fleet and personnel. This includes implementing and adhering to rigorous safety protocols, maintenance schedules, and inspection procedures as per industry best practices and regulatory requirements.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.4 Aircraft Operators shall promptly respond to communications, inquiries, and requests from GLOBALMROS and MROs regarding Service quotations, project timelines, and any other relevant matters related to the procurement of Services through the Platform.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.5 Aircraft Operators shall make timely payments to MROs for Services rendered in accordance with agreed-upon terms and conditions. Payments shall be processed through the Platform as specified in the payment terms agreed upon with MROs.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.6 Aircraft Operators are encouraged to provide constructive feedback and reviews based on their experiences with MROs and the quality of Services received through the Platform. This helps maintain transparency and improves the overall service quality within the GLOBALMROS community.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.7 Aircraft Operators shall maintain the confidentiality of any proprietary or confidential information shared by MROs through the Platform. This includes but is not limited to trade secrets, technical data, and business strategies disclosed during the course of Service agreements.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    4.8 By continuing to use the Platform, Aircraft Operators agree to abide by these obligations and acknowledge that failure to do so may result in suspension or termination of their account by GLOBALMROS, without prejudice to any other rights or remedies available under these Terms or applicable law.
</p>

<h4 style="color: #444; margin-bottom: 20px;">5. Payments</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    5.1 All payments to MROs for Services shall be made and processed through the GLOBALMROS Platform. If the parties do not invoice through our Platform for the initial work or any subsequent works arising from the request or aircraft input, GLOBALMROS shall not intervene in or consider any disputes. Furthermore, GLOBALMROS will not be liable for any financial losses incurred by either party related to such work, including but not limited to work obtained through our Platform and any subsequent work arising therefrom. Additionally, GLOBALMROS reserves the right to dismiss and delete any feedback regarding such work.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    5.2 Aircraft Operators agree to comply with the payment methods and procedures established by GLOBALMROS for processing transactions on the Platform.
</p>
<h4 style="color: #444; margin-bottom: 20px;">6. Information on the Website</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    6.1 GLOBALMROS endeavours to ensure that the information provided on the Platform is accurate and up-to-date. However, GLOBALMROS does not warrant the accuracy, completeness, or reliability of any information on the Platform, including:
</p>
<ul style="line-height: 1.6; margin-bottom: 20px;">
    <li>a) Information about MROs, their capabilities, certifications, or service offerings.</li>
    <li>b) Content posted by MROs on their profiles.</li>
    <li>c) Any technical data or specifications displayed on the Platform.</li>
</ul>
<p style="line-height: 1.6; margin-bottom: 20px;">
    6.2 GLOBALMROS reserves the right to modify, suspend, or discontinue the Platform or any part of its content at any time without prior notice.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    6.3 The Platform is provided "as is" and "as available" without any warranties or guarantees, express or implied. GLOBALMROS disclaims all warranties, including, but not limited to, warranties of merchantability, fitness for a particular purpose, non-infringement, and accuracy of information.
</p>

<h4 style="color: #444; margin-bottom: 20px;">7. Acceptable Use Policy</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    7.1 Aircraft Operators agree to use the Platform in a responsible and ethical manner that complies with all applicable laws and regulations. This includes, but is not limited to:
</p>
<ul style="line-height: 1.6; margin-bottom: 20px;">
    <li>a) Using the Platform for its intended purpose of finding qualified MROs for aircraft maintenance, repair, and overhaul services.</li>
    <li>b) Providing accurate and up-to-date information during registration and when interacting with MROs on the Platform.</li>
    <li>c) Complying with all terms and conditions of any Service agreements entered into with MROs found on the Platform.</li>
    <li>d) Not engaging in any activity that could disrupt, damage, or disable the Platform or its functionality.</li>
    <li>e) Not using the Platform to transmit any illegal, harassing, defamatory, obscene, or threatening content.</li>
    <li>f) Not impersonating any other person or entity or misrepresenting your affiliation with a particular entity.</li>
    <li>g) Not attempting to gain unauthorised access to the Platform or any other user accounts.</li>
    <li>h) Not using the Platform to collect or harvest personal data of other users without their consent.</li>
</ul>
<p style="line-height: 1.6; margin-bottom: 20px;">
    7.2 GLOBALMROS reserves the right, at its sole discretion, to take action against any Aircraft Operator who violates these Terms, including:
</p>
<ul style="line-height: 1.6; margin-bottom: 20px;">
    <li>a) Issuing warnings or notifications.</li>
    <li>b) Suspending or terminating your access to the Platform.</li>
    <li>c) Taking legal action against you.</li>
</ul>
<p style="line-height: 1.6; margin-bottom: 20px;">
    7.3 If you encounter any misuse of the Platform or any violation of these Terms, please report it to GLOBALMROS immediately through the Platform's contact form or other designated channels.
</p>

<h4 style="color: #444; margin-bottom: 20px;">8. Intellectual Property</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    8.1 Aircraft Operators acknowledge and agree that the Platform, including all software, technology, designs, trademarks, trade names, logos, and other intellectual property rights related thereto, are and shall remain the exclusive property of GLOBALMROS and its licensors.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    8.2 Subject to compliance with these Terms, GLOBALMROS grants Aircraft Operators a limited, non-exclusive, non-transferable licence to access and use the Platform solely for the purpose of procuring maintenance, repair, and overhaul services from Service Providers. This licence does not grant Aircraft Operators any rights to use the intellectual property of GLOBALMROS except as expressly authorised under these Terms.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    8.3 Aircraft Operators retain ownership of all intellectual property rights in any content they upload, post, or transmit on the Platform ("Operator Content"). By uploading, posting, or transmitting Operator Content, Aircraft Operators grant GLOBALMROS a worldwide, non-exclusive, royalty-free licence to use, reproduce, distribute, modify, adapt, and publish the Operator Content solely for the purpose of operating and promoting the Platform.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    8.4 Aircraft Operators agree not to:
</p>
<ul style="line-height: 1.6; margin-bottom: 20px;">
    <li>a) Modify, adapt, translate, reverse engineer, decompile, disassemble, or create derivative works based on the Platform or any part thereof.</li>
    <li>b) Copy, reproduce, distribute, sell, lease, sublicence, or otherwise transfer any rights in the Platform to any third party.</li>
    <li>c) Use the Platform in any manner that infringes upon the intellectual property rights or proprietary interests of GLOBALMROS or any third party.</li>
</ul>
<p style="line-height: 1.6; margin-bottom: 20px;">
    8.5 Aircraft Operators may provide suggestions, comments, or other feedback regarding the Platform ("Feedback"). Aircraft Operators acknowledge and agree that any Feedback provided to GLOBALMROS shall become the exclusive property of GLOBALMROS. GLOBALMROS may use the Feedback for any purpose without obligation of confidentiality, attribution, or compensation to Aircraft Operators.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    8.6 GLOBALMROS reserves the right to enforce its intellectual property rights to the fullest extent of the law, including seeking injunctive relief and damages against any unauthorised use of its intellectual property.
</p>

<h4 style="color: #444; margin-bottom: 20px;">9. Termination</h4>

<p style="line-height: 1.6; margin-bottom: 20px;">
    9.1 Aircraft Operators may terminate these Terms and their account on the GLOBALMROS Platform at any time by following the account termination procedures provided on the Platform. Termination shall be effective upon completion of the account closure process as specified by GLOBALMROS.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    9.2 GLOBALMROS reserves the right to suspend or terminate Aircraft Operators' access to the Platform or terminate these Terms at its sole discretion, without cause or prior notice, if:
</p>
<ul style="line-height: 1.6; margin-bottom: 20px;">
    <li>a) Aircraft Operators breach any provision of these Terms, including but not limited to non-payment of fees, violation of intellectual property rights, or misuse of the Platform.</li>
    <li>b) GLOBALMROS determines that continued use of the Platform by Aircraft Operators poses a risk to the security, integrity, or reputation of GLOBALMROS, other users, or third parties.</li>
</ul>
<p style="line-height: 1.6; margin-bottom: 20px;">
    9.3 Upon termination of these Terms or closure of Aircraft Operators' account Aircraft Operators' right to access and use the Platform shall immediately cease.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    9.4 GLOBALMROS may retain certain information and records related to Aircraft Operators' use of the Platform in accordance with its Privacy Policy or as required by law.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    9.5 Upon termination of these Terms, any provisions that by their nature should survive termination shall survive, including but not limited to provisions related to intellectual property rights, limitations of liability, indemnification, and dispute resolution.
</p>
<p style="line-height: 1.6; margin-bottom: 20px;">
    9.6 In the event of termination by GLOBALMROS, notice may be provided to Aircraft Operators via email or through the Platform's messaging system. It is the responsibility of Aircraft Operators to ensure that their contact information on the Platform is accurate and up to date.
</p>
<h4>10. Limitation of Liability and Indemnification</h4>
<p>10.1 To the fullest extent permitted by law, GLOBALMROS shall not be liable to Aircraft Operators for any indirect, incidental, consequential, special, or punitive damages, including but not limited to loss of profits, loss of revenue, loss of business opportunity, or loss of data, arising out of or in connection with these Terms, the use of the Platform, or the Services provided by Service Providers, even if GLOBALMROS has been advised of the possibility of such damages.</p>
<p>10.2 GLOBALMROS's total liability for any claim arising out of or in connection with these Terms, whether in contract, tort (including negligence), breach of statutory duty, or otherwise, shall not exceed the total fees paid by Aircraft Operators to GLOBALMROS during the twelve (12) months immediately preceding the event giving rise to liability.</p>
<p>10.3 Nothing in these Terms shall exclude or limit liability for death or personal injury caused by negligence, fraud or fraudulent misrepresentation, or any other liability that cannot be excluded or limited under applicable law.</p>
<p>10.4 Aircraft Operators agree to indemnify, defend, and hold harmless GLOBALMROS and its officers, directors, employees, agents, and affiliates from and against any and all claims, liabilities, damages, losses, costs, expenses (including legal fees), or demands made by any third party due to or arising out of:</p>
<ul>
    <li>a) Aircraft Operators' use of the Platform or Services.</li>
    <li>b) Breach of these Terms by Aircraft Operators.</li>
    <li>c) Violation of any rights of another party, including intellectual property rights, by Aircraft Operators.</li>
</ul>
<p>10.5 GLOBALMROS shall promptly notify Aircraft Operators in writing of any indemnifiable claim. Aircraft Operators shall have the right to assume the defence and control of any such claim with counsel of their choice, provided that GLOBALMROS may participate in the defence and settlement of the claim at its own expense.</p>
<p>10.6 The limitations and exclusions of liability and indemnities set forth in this clause 10 are fundamental elements of the basis of the bargain between Aircraft Operators and GLOBALMROS, and GLOBALMROS would not have entered into these Terms without such limitations, exclusions, and indemnities.</p>

<h4>11. Miscellaneous</h4>
<p>11.1 Governing Law: These Terms shall be governed by and construed in accordance with English law. Any disputes arising under these Terms shall be subject to the exclusive jurisdiction of the English courts.</p>
<p>11.2 Amendments: GLOBALMROS reserves the right to modify or amend these Terms at any time. Notice of such changes will be communicated to Aircraft Operators through the Platform.</p>
<p>11.3 Modifications to Services: GLOBALMROS reserves the right to modify or discontinue any part of the Platform or services offered therein without notice. Aircraft Operators acknowledge and agree that GLOBALMROS shall not be liable to them or any third party for any modification, suspension, or discontinuance of the Platform.</p>
<p>11.4 Force Majeure: GLOBALMROS shall not be liable for any failure or delay in performing its obligations under these Terms due to causes beyond its reasonable control, including but not limited to acts of God, war, terrorism, pandemics, strikes, or natural disasters.</p>
<p>11.5 Entire Agreement: These Terms constitute the entire agreement between Aircraft Operators and GLOBALMROS regarding the use of the Platform, superseding any prior agreements or understandings.</p>
<p>11.6 Third Party Links: Aircraft Operators acknowledge that the Platform may contain links to third-party websites or resources. GLOBALMROS is not responsible for the availability, accuracy, or content of such third-party sites and resources, and does not endorse any products, services, or content offered through them.</p>
<p>11.7 Severability: If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions shall continue to be valid and enforceable to the fullest extent permitted by law.</p>
<p>11.8 Waiver: The failure of GLOBALMROS to enforce any right or provision of these Terms shall not constitute a waiver of that right or provision unless acknowledged and agreed to by GLOBALMROS in writing.</p>
</div>
            </div>
        </div>
    </section>
</main>

<!-- jQuery Script to toggle between sections -->
<?php
$script = <<<JS
(function ($) {
    'use strict';

    // Switches between MRO and AO legal sections without reloading the page.
    function showTerms(target) {
        $('.can-tab-btn')
            .removeClass('active')
            .attr('aria-selected', 'false');

        $('.can-tab-btn[data-target="' + target + '"]')
            .addClass('active')
            .attr('aria-selected', 'true');

        $('.can-terms-pane')
            .removeClass('is-active')
            .hide();

        $(target)
            .fadeIn(160)
            .addClass('is-active');
    }

    $('#mro-btn, #ao-btn').on('click', function () {
        showTerms($(this).data('target'));
    });
})(jQuery);
JS;
$this->registerJs($script);
?>
