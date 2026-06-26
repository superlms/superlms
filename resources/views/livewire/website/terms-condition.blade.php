<div x-data="{ showMore: false }">
    <!-- Terms & Conditions section -->
    <section id="terms-condition" class="py-16 px-4 bg-white">
        <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">Terms & Conditions</h2>
        <div class="max-w-4xl mx-auto bg-gray-50 p-8 rounded-lg shadow-md">

            <!-- Always Visible Content -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800">Introduction</h3>
                <p class="mb-6">
                    These terms and conditions apply to all Users who access the EdyoneLms Platform, including the 'EdyoneLms' application and www.edyonelms.in ('Website') and services managed by the Company. Users are required to read and understand these terms before submitting any Personal Information. By using our services, you consent to the collection, processing, and use of your information as described in these terms and our Privacy Policy. EdyoneLms does not guarantee the accuracy, integrity, or quality of information on third-party websites or applications and encourages users to review their respective terms and privacy policies. We have implemented reasonable precautions under applicable Indian law to protect Personal Information from unauthorized access, misuse, disclosure, modification, or loss.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Usage and Retention of Information</h3>
                <p class="mb-6">
                    We collect information provided during registration or use of the Application/Website, including: (a) your name, age, email address, location, phone number, and educational interests; (b) transaction-related information, such as purchase details; (c) information provided when contacting us for support; and (d) information entered during activities like asking doubts, participating in discussions, or taking tests. This information is used to provide, analyze, and improve our services, offer a personalized experience, contact you regarding your account, provide customer service, deliver personalized marketing, and detect or prevent fraudulent or illegal activities.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Cookies</h3>
                <p class="mb-6">
                    We may use cookies—small files stored on your computer—to uniquely identify your browser, track preferences, enable faster logins, and analyze user trends to improve our offerings. Most browsers accept cookies by default, but you can configure your browser to refuse cookies or notify you when they are sent. Note that disabling cookies may affect the functionality of some features and services.
                </p>
            </div>

            <!-- Hidden Content -->
            <div x-show="showMore" x-collapse>
                <h3 class="text-xl font-semibold text-gray-800">Information Security</h3>
                <p class="mb-6">
                    We do not sell, transfer, or rent your personal information to third parties for marketing purposes without your explicit consent. Protecting your privacy is a core principle, and we treat your information as a critical asset. Your personal information is stored and processed on secure computers in India, protected by physical and technological security measures. We do not share your information with other companies for their marketing purposes without your consent.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">User Customization</h3>
                <p class="mb-6">
                    You can customize how we use your personal information for communication, marketing, advertising, and account management. To opt out of marketing communications, use the unsubscribe link in emails, update your communication preferences, or contact us via email to block promotional messages. We do not sell or rent your personal information to third parties for marketing purposes without your explicit consent.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Ownership of Rights</h3>
                <p class="mb-6">
                    All rights, including copyright, in the Website/Application are owned by or licensed to EdyoneLms. Any use of the Website/Application or its contents, including copying or storing, beyond personal, non-commercial use, is prohibited without our permission. You may not modify, distribute, transmit, display, print, publish, sell, license, create derivative works, or use content for commercial or public purposes. Trademarks on our Website/Application remain the exclusive property of EdyoneLms or their respective owners.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Consent</h3>
                <p class="mb-6">
                    By registering, you expressly consent to our collection, processing, storing, and handling of your information as outlined in this Policy. For minors, consent is deemed to be provided by their parents or guardians. All processing activities, including collecting, storing, deleting, using, combining, sharing, transferring, and disclosing information, will occur in India in accordance with applicable data protection laws. You also consent to EdyoneLms using your name, age, photograph, videos, voice recordings, rank, statements, or testimonials ("Personal Attributes") for promotional purposes across various media without further notice or compensation. Marketing materials created using your Personal Attributes remain the sole property of EdyoneLms, and you waive any claims related to their use.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Exclusive Service</h3>
                <p class="mb-6">
                    By maintaining an EdyoneLms account, you consent to the use of camera and microphone permissions for video calls and recordings.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Security</h3>
                <p class="mb-6">
                    We prioritize safeguarding your information using physical, electronic, and procedural measures. Access to your information is restricted to authorized employees who need it to operate, develop, or improve our services. While we strive to maintain robust security, no system can prevent all potential breaches.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Disclosure of Information</h3>
                <p class="mb-4"><strong>EdyoneLms may disclose information in the following situations:</strong></p>
                <ul class="list-disc pl-6 text-gray-700 space-y-1">
                    <li>As required by law;</li>
                    <li>To enforce these Terms and Conditions, including investigating potential violations;</li>
                    <li>When we believe in good faith that disclosure is necessary to protect our rights, your safety, or others, investigate fraud, address security/technical issues, or respond to government requests;</li>
                    <li>With trusted service providers.</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-800">Updates to Policy</h3>
                <p class="mb-6">
                    Our Privacy Policy may evolve to address new circumstances. You are advised to review this Policy regularly, as continued use constitutes approval of any changes.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Limitation of Liability</h3>
                <p class="mb-6">
                    EdyoneLms is not the manufacturer of content on the Website, Application, or YouTube Channel and is not liable for any repercussions arising from such content. The Company, its officers, directors, employees, partners, or agents shall not be liable for any special, incidental, indirect, consequential, or punitive damages, including loss of use, data, or profits, arising from your use of or access to the Website/Application/Services. In case of a breach of these Terms, the Company may seek an injunction and other remedies, and your account may be suspended or terminated. Violations may also result in civil or criminal liability under applicable laws.
                </p>

                <h3 class="text-xl font-semibold text-gray-800">Refund Policy</h3>
                <p class="mb-6">
                    Purchases of our products/services are non-refundable. If you purchase an online batch/service by mistake, you may request to switch to another batch of equal value within 10 days of purchase.
                </p>
            </div>

            <!-- Toggle Button -->
            <div class="text-center">
                <button @click="showMore = !showMore"
                    class="bg-gradient-to-r from-blue-500 to-purple-500 text-white px-6 py-3 rounded-full font-semibold hover:shadow-lg transition mt-4">
                    <span x-show="!showMore">Read Full Terms</span>
                    <span x-show="showMore">Show Less</span>
                </button>
            </div>
        </div>
    </section>
</div>