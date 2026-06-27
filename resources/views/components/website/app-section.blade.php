<style>
    .superlms-app-section {
        padding: 80px 6%;
        background: linear-gradient(135deg, var(--primary-faint, #F9EDF5), var(--secondary-faint, #F0EDFF));
        border-top: 1px solid var(--border2, rgba(111,86,254,0.08));
        border-bottom: 1px solid var(--border2, rgba(111,86,254,0.08));
        font-family: 'DM Sans', sans-serif;
        color: var(--text, #1A0F2E);
        box-sizing: border-box;
    }

    .superlms-app-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        max-width: 1060px;
        margin: 0 auto;
    }

    .superlms-section-tag {
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

    .superlms-app-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(28px, 3vw, 40px);
        font-weight: 600;
        line-height: 1.15;
        color: var(--text, #1A0F2E);
        margin: 0 0 16px;
    }

    .superlms-app-title .gradient-text {
        background: var(--grad1, linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .superlms-app-desc {
        font-size: 15px;
        color: var(--text3, #6B5B8A);
        line-height: 1.8;
        margin: 0 0 28px;
    }

    .superlms-store-buttons {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 28px;
    }

    .superlms-store-btn {
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

    .superlms-store-btn:hover {
        border-color: var(--violet, #6F56FE);
        transform: translateY(-3px);
        box-shadow: 0 2px 12px rgba(111, 86, 254, 0.08);
    }

    .superlms-store-btn__meta {
        display: flex;
        flex-direction: column;
    }

    .superlms-store-btn__sub {
        font-size: 10px;
        color: var(--text3, #6B5B8A);
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }

    .superlms-store-btn__name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text, #1A0F2E);
    }

    .superlms-phone-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .superlms-phone-mockup {
        width: 250px;
        height: 500px;
        background: #F7F4FF;
        border: 1.5px solid var(--border, rgba(111,86,254,0.15));
        border-radius: 36px;
        margin: 0 auto;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(111, 86, 254, 0.12);
        animation: superlmsFloat 5s ease-in-out infinite;
    }

    .superlms-phone-notch {
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

    .superlms-phone-screen {
        padding: 46px 14px 14px;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: linear-gradient(160deg, #FAFAFA, #F7F4FF);
    }

    .superlms-phone-header {
        background: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
        border-radius: 12px;
        padding: 14px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        color: #fff;
    }

    .superlms-phone-card {
        background: #fff;
        border: 1px solid var(--border2, rgba(111,86,254,0.08));
        border-radius: 10px;
        padding: 10px;
    }

    .superlms-phone-card__title {
        font-size: 11px;
        font-weight: 600;
        color: var(--text, #1A0F2E);
        margin-bottom: 5px;
    }

    .superlms-phone-bar {
        height: 3px;
        border-radius: 2px;
        background: linear-gradient(135deg, #DB57B2 0%, #6F56FE 100%);
        margin-bottom: 4px;
    }

    .superlms-phone-label {
        font-size: 9px;
        color: var(--text3, #6B5B8A);
    }

    /* ── Dashboard-style phone screen ── */
    .superlms-dash-head {
        display: flex;
        align-items: center;
        gap: 9px;
        background: linear-gradient(135deg, #6F56FE 0%, #DB57B2 100%);
        border-radius: 14px;
        padding: 12px 13px;
        color: #fff;
    }

    .superlms-dash-avatar {
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

    .superlms-dash-hi {
        font-size: 9px;
        opacity: 0.85;
        line-height: 1.2;
    }

    .superlms-dash-name {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
    }

    .superlms-dash-bell {
        margin-left: auto;
        font-size: 13px;
    }

    .superlms-kpi-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .superlms-kpi {
        background: #fff;
        border: 1px solid var(--border2, rgba(111, 86, 254, 0.08));
        border-radius: 11px;
        padding: 9px 10px;
    }

    .superlms-kpi__num {
        font-size: 15px;
        font-weight: 700;
        background: linear-gradient(135deg, #6F56FE, #DB57B2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.1;
    }

    .superlms-kpi__label {
        font-size: 8px;
        color: var(--text3, #6B5B8A);
        margin-top: 1px;
    }

    .superlms-mini-chart {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        height: 46px;
        margin-top: 8px;
    }

    .superlms-mini-chart span {
        flex: 1;
        border-radius: 3px 3px 0 0;
        background: linear-gradient(180deg, #6F56FE, #DB57B2);
        transform-origin: bottom;
        animation: superlmsBar .9s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }

    @keyframes superlmsBar {
        from { transform: scaleY(0); }
        to   { transform: scaleY(1); }
    }

    .superlms-quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }

    .superlms-quick {
        background: #fff;
        border: 1px solid var(--border2, rgba(111, 86, 254, 0.08));
        border-radius: 9px;
        padding: 7px 2px;
        text-align: center;
    }

    .superlms-quick__icon {
        font-size: 14px;
    }

    .superlms-quick__label {
        font-size: 7px;
        color: var(--text3, #6B5B8A);
        margin-top: 2px;
    }

    @keyframes superlmsFloat {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-14px); }
    }

    @media (max-width: 1100px) {
        .superlms-app-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        .superlms-phone-mockup {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .superlms-app-section {
            padding: 52px 4%;
        }
        .superlms-store-buttons {
            flex-direction: column;
        }
        .superlms-store-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<section class="superlms-app-section">
    <div class="superlms-app-grid">

        {{-- LEFT: Text content --}}
        <div>
            <span class="superlms-section-tag">Mobile Apps</span>

            <h2 class="superlms-app-title">
                Learn Anywhere,
                <span class="gradient-text">Anytime</span>
            </h2>

            <p class="superlms-app-desc">
                Learn anytime, anywhere with SUPERLMS mobile apps. Stay connected to
                classes, assignments, and school updates on the go. Access lessons, track
                progress, communicate with teachers, and manage your learning seamlessly,
                ensuring uninterrupted education and real-time engagement, all from the
                convenience of your smartphone or tablet.
            </p>

            <div class="superlms-store-buttons">
                <a class="superlms-store-btn"
                    href="https://play.google.com/store/apps/details?id=com.edyoneapp&pcampaignid=web_share"
                    target="_blank" rel="noopener noreferrer">
                    <svg width="32" height="32" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                        <path d="M48 432L240 256 48 80v352z" fill="#EA4335" />
                        <path d="M336 176L48 80l192 176 96-80z" fill="#FBBC04" />
                        <path d="M336 336l-96-80L48 432l288-96z" fill="#34A853" />
                        <path d="M464 256l-128-80-96 80 96 80 128-80z" fill="#4285F4" />
                    </svg>
                    <div class="superlms-store-btn__meta">
                        <span class="superlms-store-btn__sub">Get it on</span>
                        <span class="superlms-store-btn__name">Google Play</span>
                    </div>
                </a>
            </div>
        </div>

        {{-- RIGHT: Phone mockup --}}
        <div class="superlms-phone-wrap">
            <div class="superlms-phone-mockup">
                <div class="superlms-phone-notch"></div>
                <div class="superlms-phone-screen">
                    {{-- Greeting header --}}
                    <div class="superlms-dash-head">
                        <div class="superlms-dash-avatar">🎓</div>
                        <div>
                            <div class="superlms-dash-hi">Good morning,</div>
                            <div class="superlms-dash-name">Aarav's Dashboard</div>
                        </div>
                        <div class="superlms-dash-bell">🔔</div>
                    </div>

                    {{-- KPI tiles --}}
                    <div class="superlms-kpi-grid">
                        <div class="superlms-kpi">
                            <div class="superlms-kpi__num">94%</div>
                            <div class="superlms-kpi__label">Attendance</div>
                        </div>
                        <div class="superlms-kpi">
                            <div class="superlms-kpi__num">A+</div>
                            <div class="superlms-kpi__label">Avg. Grade</div>
                        </div>
                        <div class="superlms-kpi">
                            <div class="superlms-kpi__num">3</div>
                            <div class="superlms-kpi__label">Tasks Due</div>
                        </div>
                        <div class="superlms-kpi">
                            <div class="superlms-kpi__num">Paid</div>
                            <div class="superlms-kpi__label">Fee Status</div>
                        </div>
                    </div>

                    {{-- Performance chart --}}
                    <div class="superlms-phone-card">
                        <div class="superlms-phone-card__title">Performance Overview</div>
                        <div class="superlms-mini-chart">
                            <span style="height:55%;animation-delay:.05s"></span>
                            <span style="height:72%;animation-delay:.13s"></span>
                            <span style="height:48%;animation-delay:.21s"></span>
                            <span style="height:88%;animation-delay:.29s"></span>
                            <span style="height:66%;animation-delay:.37s"></span>
                            <span style="height:95%;animation-delay:.45s"></span>
                        </div>
                        <div class="superlms-phone-label" style="margin-top:6px;">Steady improvement this term 📈</div>
                    </div>

                    {{-- Quick actions --}}
                    <div class="superlms-quick-grid">
                        <div class="superlms-quick">
                            <div class="superlms-quick__icon">📚</div>
                            <div class="superlms-quick__label">Classes</div>
                        </div>
                        <div class="superlms-quick">
                            <div class="superlms-quick__icon">📝</div>
                            <div class="superlms-quick__label">Homework</div>
                        </div>
                        <div class="superlms-quick">
                            <div class="superlms-quick__icon">🗓️</div>
                            <div class="superlms-quick__label">Timetable</div>
                        </div>
                        <div class="superlms-quick">
                            <div class="superlms-quick__icon">💳</div>
                            <div class="superlms-quick__label">Fees</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
