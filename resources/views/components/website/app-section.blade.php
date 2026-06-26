<style>
    .edyone-app-section {
        padding: 80px 6%;
        background: linear-gradient(135deg, var(--primary-faint, #F9EDF5), var(--secondary-faint, #F0EDFF));
        border-top: 1px solid var(--border2, rgba(111,86,254,0.08));
        border-bottom: 1px solid var(--border2, rgba(111,86,254,0.08));
        font-family: 'DM Sans', sans-serif;
        color: var(--text, #1A0F2E);
        box-sizing: border-box;
    }

    .edyone-app-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        max-width: 1060px;
        margin: 0 auto;
    }

    .edyone-section-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 20px;
        background: var(--secondary-faint, #F0EDFF);
        border: 1px solid var(--border, rgba(111,86,254,0.15));
        color: var(--violet, #6F56FE);
    }

    .edyone-app-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 600;
        line-height: 1.15;
        color: var(--text, #1A0F2E);
        margin: 0 0 16px;
    }

    .edyone-app-title .gradient-text {
        background: var(--grad1, linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .edyone-app-desc {
        font-size: 15px;
        color: var(--text3, #6B5B8A);
        line-height: 1.8;
        margin: 0 0 28px;
    }

    .edyone-store-buttons {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 28px;
    }

    .edyone-store-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 22px;
        background: #fff;
        border: 1px solid var(--border2, rgba(111,86,254,0.08));
        border-radius: var(--radius-sm, 10px);
        text-decoration: none;
        color: var(--text, #1A0F2E);
        transition: border-color .3s, transform .3s, box-shadow .3s;
    }

    .edyone-store-btn:hover {
        border-color: var(--violet, #6F56FE);
        transform: translateY(-3px);
        box-shadow: 0 2px 12px rgba(111, 86, 254, 0.08);
    }

    .edyone-store-btn__meta {
        display: flex;
        flex-direction: column;
    }

    .edyone-store-btn__sub {
        font-size: 10px;
        color: var(--text3, #6B5B8A);
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }

    .edyone-store-btn__name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text, #1A0F2E);
    }

    .edyone-phone-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .edyone-phone-mockup {
        width: 250px;
        height: 500px;
        background: #F7F4FF;
        border: 1.5px solid var(--border, rgba(111,86,254,0.15));
        border-radius: 36px;
        margin: 0 auto;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(111, 86, 254, 0.12);
        animation: edyoneFloat 5s ease-in-out infinite;
    }

    .edyone-phone-notch {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 88px;
        height: 24px;
        background: #EDE8FF;
        border-radius: 12px;
        z-index: 10;
    }

    .edyone-phone-screen {
        padding: 46px 14px 14px;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: linear-gradient(160deg, #FAFAFA, #F7F4FF);
    }

    .edyone-phone-header {
        background: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
        border-radius: 12px;
        padding: 14px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        color: #fff;
    }

    .edyone-phone-card {
        background: #fff;
        border: 1px solid var(--border2, rgba(111,86,254,0.08));
        border-radius: 10px;
        padding: 10px;
    }

    .edyone-phone-card__title {
        font-size: 11px;
        font-weight: 600;
        color: var(--text, #1A0F2E);
        margin-bottom: 5px;
    }

    .edyone-phone-bar {
        height: 3px;
        border-radius: 2px;
        background: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
        margin-bottom: 4px;
    }

    .edyone-phone-label {
        font-size: 9px;
        color: var(--text3, #6B5B8A);
    }

    /* ── Dashboard-style phone screen ── */
    .edyone-dash-head {
        display: flex;
        align-items: center;
        gap: 9px;
        background: linear-gradient(135deg, #6F56FE 0%, #DB57B2 100%);
        border-radius: 14px;
        padding: 12px 13px;
        color: #fff;
    }

    .edyone-dash-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .edyone-dash-hi {
        font-size: 9px;
        opacity: 0.85;
        line-height: 1.2;
    }

    .edyone-dash-name {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
    }

    .edyone-dash-bell {
        margin-left: auto;
        font-size: 13px;
    }

    .edyone-kpi-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .edyone-kpi {
        background: #fff;
        border: 1px solid var(--border2, rgba(111, 86, 254, 0.08));
        border-radius: 11px;
        padding: 9px 10px;
    }

    .edyone-kpi__num {
        font-size: 15px;
        font-weight: 700;
        background: linear-gradient(135deg, #6F56FE, #DB57B2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.1;
    }

    .edyone-kpi__label {
        font-size: 8px;
        color: var(--text3, #6B5B8A);
        margin-top: 1px;
    }

    .edyone-mini-chart {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        height: 46px;
        margin-top: 8px;
    }

    .edyone-mini-chart span {
        flex: 1;
        border-radius: 3px 3px 0 0;
        background: linear-gradient(180deg, #6F56FE, #DB57B2);
        transform-origin: bottom;
        animation: edyoneBar .9s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes edyoneBar {
        from { transform: scaleY(0); }
        to   { transform: scaleY(1); }
    }

    .edyone-quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .edyone-quick {
        background: #fff;
        border: 1px solid var(--border2, rgba(111, 86, 254, 0.08));
        border-radius: 9px;
        padding: 7px 2px;
        text-align: center;
    }

    .edyone-quick__icon {
        font-size: 14px;
    }

    .edyone-quick__label {
        font-size: 7px;
        color: var(--text3, #6B5B8A);
        margin-top: 2px;
    }

    @keyframes edyoneFloat {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-14px); }
    }

    @media (max-width: 1100px) {
        .edyone-app-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .edyone-phone-mockup {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .edyone-app-section {
            padding: 52px 4%;
        }
        .edyone-store-buttons {
            flex-direction: column;
        }
        .edyone-store-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<section class="edyone-app-section">
    <div class="edyone-app-grid">

        {{-- LEFT: Text content --}}
        <div>
            <span class="edyone-section-tag">Mobile Apps</span>

            <h2 class="edyone-app-title">
                Learn Anywhere,
                <span class="gradient-text">Anytime</span>
            </h2>

            <p class="edyone-app-desc">
                Learn anytime, anywhere with EDYONE LMS mobile apps. Stay connected to
                classes, assignments, and school updates on the go. Access lessons, track
                progress, communicate with teachers, and manage your learning seamlessly,
                ensuring uninterrupted education and real-time engagement, all from the
                convenience of your smartphone or tablet.
            </p>

            <div class="edyone-store-buttons">
                <a class="edyone-store-btn"
                    href="https://play.google.com/store/apps/details?id=com.edyoneapp&pcampaignid=web_share"
                    target="_blank" rel="noopener noreferrer">
                    <svg width="32" height="32" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                        <path d="M48 432L240 256 48 80v352z" fill="#EA4335" />
                        <path d="M336 176L48 80l192 176 96-80z" fill="#FBBC04" />
                        <path d="M336 336l-96-80L48 432l288-96z" fill="#34A853" />
                        <path d="M464 256l-128-80-96 80 96 80 128-80z" fill="#4285F4" />
                    </svg>
                    <div class="edyone-store-btn__meta">
                        <span class="edyone-store-btn__sub">Get it on</span>
                        <span class="edyone-store-btn__name">Google Play</span>
                    </div>
                </a>
            </div>
        </div>

        {{-- RIGHT: Phone mockup --}}
        <div class="edyone-phone-wrap">
            <div class="edyone-phone-mockup">
                <div class="edyone-phone-notch"></div>
                <div class="edyone-phone-screen">
                    {{-- Greeting header --}}
                    <div class="edyone-dash-head">
                        <div class="edyone-dash-avatar">🎓</div>
                        <div>
                            <div class="edyone-dash-hi">Good morning,</div>
                            <div class="edyone-dash-name">Aarav's Dashboard</div>
                        </div>
                        <div class="edyone-dash-bell">🔔</div>
                    </div>

                    {{-- KPI tiles --}}
                    <div class="edyone-kpi-grid">
                        <div class="edyone-kpi">
                            <div class="edyone-kpi__num">94%</div>
                            <div class="edyone-kpi__label">Attendance</div>
                        </div>
                        <div class="edyone-kpi">
                            <div class="edyone-kpi__num">A+</div>
                            <div class="edyone-kpi__label">Avg. Grade</div>
                        </div>
                        <div class="edyone-kpi">
                            <div class="edyone-kpi__num">3</div>
                            <div class="edyone-kpi__label">Tasks Due</div>
                        </div>
                        <div class="edyone-kpi">
                            <div class="edyone-kpi__num">Paid</div>
                            <div class="edyone-kpi__label">Fee Status</div>
                        </div>
                    </div>

                    {{-- Performance chart --}}
                    <div class="edyone-phone-card">
                        <div class="edyone-phone-card__title">Performance Overview</div>
                        <div class="edyone-mini-chart">
                            <span style="height:55%;animation-delay:.05s"></span>
                            <span style="height:72%;animation-delay:.13s"></span>
                            <span style="height:48%;animation-delay:.21s"></span>
                            <span style="height:88%;animation-delay:.29s"></span>
                            <span style="height:66%;animation-delay:.37s"></span>
                            <span style="height:95%;animation-delay:.45s"></span>
                        </div>
                        <div class="edyone-phone-label" style="margin-top:6px;">Steady improvement this term 📈</div>
                    </div>

                    {{-- Quick actions --}}
                    <div class="edyone-quick-grid">
                        <div class="edyone-quick">
                            <div class="edyone-quick__icon">📚</div>
                            <div class="edyone-quick__label">Classes</div>
                        </div>
                        <div class="edyone-quick">
                            <div class="edyone-quick__icon">📝</div>
                            <div class="edyone-quick__label">Homework</div>
                        </div>
                        <div class="edyone-quick">
                            <div class="edyone-quick__icon">🗓️</div>
                            <div class="edyone-quick__label">Timetable</div>
                        </div>
                        <div class="edyone-quick">
                            <div class="edyone-quick__icon">💳</div>
                            <div class="edyone-quick__label">Fees</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
