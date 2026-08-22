@extends('layouts.app')

@section('title', 'Welcome to Capital First')

@section('content')
<div class="welcome-wrapper" style="overflow-x: hidden; position: relative;">
    
    <!-- Ambient Background -->
    <div class="ambient-bg" style="position: absolute; top: -20%; left: 50%; transform: translateX(-50%); width: 100vw; height: 100vh; background: radial-gradient(circle, rgba(0, 240, 255, 0.15) 0%, rgba(11, 14, 20, 0) 60%); z-index: -1; pointer-events: none;"></div>

    <!-- HERO SECTION -->
    <div class="container hero-section" style="text-align: center; padding-top: 6rem; padding-bottom: 4rem;">
        <div class="fade-up-1">
            <div style="display: inline-block; padding: 0.5rem 1rem; border-radius: 2rem; background: rgba(0, 240, 255, 0.1); border: 1px solid rgba(0, 240, 255, 0.2); color: var(--accent-neon); font-size: 0.875rem; font-weight: 600; margin-bottom: 1.5rem;">
                🚀 Next-Gen Algorithmic Trading
            </div>
            <h1 style="font-size: clamp(3rem, 6vw, 5rem); font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -1px;">
                Automate Your <br />
                <span class="text-gradient">Crypto Trading</span>
            </h1>
            <p class="text-secondary" style="font-size: clamp(1.1rem, 2vw, 1.25rem); max-width: 650px; margin: 0 auto; line-height: 1.6;">
                Connect Binance, Delta Exchange, or MetaTrader to execute algorithmic strategies with sub-second latency and absolute precision. Maximize profits while you sleep.
            </p>
        </div>

        <div class="fade-up-2" style="margin-top: 3rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('register') }}" class="btn btn-primary pulse-btn" style="font-size: 1.125rem; padding: 1rem 2.5rem;">Start Trading Now</a>
            <a href="#features" class="btn btn-outline" style="font-size: 1.125rem; padding: 1rem 2.5rem;">View Features</a>
        </div>
    </div>

    <!-- CSS DASHBOARD MOCKUP -->
    <div class="container fade-up-3" style="position: relative; margin-top: 2rem; margin-bottom: 5rem;">
        <div class="mockup-container" style="border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(18, 22, 28, 0.8); backdrop-filter: blur(20px); box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(0, 240, 255, 0.1); max-width: 1000px; margin: 0 auto; overflow: hidden; position: relative;">
            
            <!-- Mockup Header -->
            <div style="height: 40px; background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; align-items: center; padding: 0 1rem; gap: 8px;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #ff5f56;"></div>
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #ffbd2e;"></div>
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #27c93f;"></div>
                <div style="margin-left: 20px; color: rgba(255,255,255,0.3); font-size: 12px; font-family: monospace;">capitalfirst.app/dashboard</div>
            </div>

            <!-- Mockup Body -->
            <div class="mockup-body" style="padding: 2rem; gap: 1.5rem;">
                <div>
                    <h4 style="margin-top:0; color: #fff; font-size: 1.5rem; margin-bottom: 1.5rem;">Portfolio Overview</h4>
                    <div class="mockup-stats" style="gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border-left: 3px solid var(--accent-neon);">
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-bottom: 5px;">Allocated Capital</div>
                            <div style="color: #fff; font-size: 1.5rem; font-weight: bold;">$124,500.00</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border-left: 3px solid var(--accent-green);">
                            <div style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-bottom: 5px;">Realized PnL</div>
                            <div style="color: var(--accent-green); font-size: 1.5rem; font-weight: bold;">+$18,240.50</div>
                        </div>
                    </div>
                    <div style="height: 180px; background: linear-gradient(180deg, rgba(0, 240, 255, 0.1) 0%, rgba(0,0,0,0) 100%); border-bottom: 2px solid var(--accent-neon); position: relative; border-radius: 4px;">
                        <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none" style="position: absolute; bottom: 0;">
                            <path d="M0,80 Q25,90 50,50 T100,20 L100,100 L0,100 Z" fill="rgba(0, 240, 255, 0.1)"></path>
                            <path d="M0,80 Q25,90 50,50 T100,20" fill="none" stroke="var(--accent-neon)" stroke-width="2"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h4 style="margin-top:0; color: #fff; font-size: 1rem; margin-bottom: 1rem;">Live Trades</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                        <div style="background: rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 6px; display: flex; justify-content: space-between;">
                            <div>
                                <span style="color: var(--accent-green); font-weight: bold; font-size: 0.8rem;">LONG</span>
                                <span style="color: #fff; font-size: 0.8rem; margin-left: 5px;">BTC/USD</span>
                            </div>
                            <span style="color: var(--accent-green); font-size: 0.8rem;">+$240.10</span>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 6px; display: flex; justify-content: space-between;">
                            <div>
                                <span style="color: var(--accent-red); font-weight: bold; font-size: 0.8rem;">SHORT</span>
                                <span style="color: #fff; font-size: 0.8rem; margin-left: 5px;">ETH/USD</span>
                            </div>
                            <span style="color: var(--accent-red); font-size: 0.8rem;">-$45.20</span>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 0.8rem; border-radius: 6px; display: flex; justify-content: space-between;">
                            <div>
                                <span style="color: var(--accent-green); font-weight: bold; font-size: 0.8rem;">LONG</span>
                                <span style="color: #fff; font-size: 0.8rem; margin-left: 5px;">SOL/USD</span>
                            </div>
                            <span style="color: var(--accent-green); font-size: 0.8rem;">+$12.50</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shimmer effect -->
            <div class="shimmer" style="position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent); transform: skewX(-20deg);"></div>
        </div>
    </div>

    <!-- INTEGRATIONS SECTION -->
    <div style="padding: 4rem 0; border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
        <div class="container" style="text-align: center;">
            <p style="color: rgba(255,255,255,0.4); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; margin-bottom: 2rem;">Seamlessly integrated with top exchanges</p>
            <div style="display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; opacity: 0.6; filter: grayscale(100%);">
                <!-- Using generic text logos as placeholders for brand logos -->
                <h2 style="margin: 0; font-family: sans-serif; font-weight: 900; letter-spacing: -1px;">BINANCE</h2>
                <h2 style="margin: 0; font-family: serif; font-weight: bold; font-style: italic;">Delta Exchange</h2>
                <h2 style="margin: 0; font-family: sans-serif; font-weight: 400;"><span style="font-weight: 900;">Meta</span>Trader 4/5</h2>
            </div>
        </div>
    </div>

    <!-- LIVE TICKER -->
    <div class="ticker-wrapper" style="overflow: hidden; white-space: nowrap; padding: 1rem 0; background: var(--accent-blue); color: #fff; font-weight: bold; font-size: 0.9rem; letter-spacing: 1px;">
        <div class="ticker-track" style="display: inline-block;">
            @for ($i = 0; $i < 4; $i++)
            <span style="margin: 0 2rem;">⚡ Over $10M+ Volume Automated</span> • 
            <span style="margin: 0 2rem;">📈 BTC/USD $68,240</span> • 
            <span style="margin: 0 2rem;">🔥 0.5ms Execution Latency</span> • 
            <span style="margin: 0 2rem;">🛡️ Bank-grade Encryption</span> •
            @endfor
        </div>
    </div>

    <!-- FEATURES SECTION -->
    <div class="container" id="features" style="padding: 8rem 1rem;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 3rem; margin-bottom: 1rem;">Built for <span style="color: #fff;">Performance</span></h2>
            <p class="text-secondary" style="max-width: 600px; margin: 0 auto; font-size: 1.1rem;">Everything you need to scale your trading operations without compromising on speed or security.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            
            <div class="glass-panel feature-card">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(0, 240, 255, 0.1); color: var(--accent-neon); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 style="color: #fff; margin-bottom: 1rem; font-size: 1.25rem;">Ultra-Low Latency</h3>
                <p class="text-secondary" style="line-height: 1.6;">Execute trades instantly across multiple broker APIs directly from your VPS to the exchange. Skip the queue.</p>
            </div>

            <div class="glass-panel feature-card">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(39, 201, 63, 0.1); color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 style="color: #fff; margin-bottom: 1rem; font-size: 1.25rem;">Total Control</h3>
                <p class="text-secondary" style="line-height: 1.6;">Your API keys are encrypted at rest. Manage risk parameters with our hard max-drawdown limits to protect your capital.</p>
            </div>

            <div class="glass-panel feature-card">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(179, 136, 255, 0.1); color: #b388ff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.5rem;">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h3 style="color: #fff; margin-bottom: 1rem; font-size: 1.25rem;">Multi-Broker Setup</h3>
                <p class="text-secondary" style="line-height: 1.6;">Run Binance Spot, Delta India Futures, and MT4/MT5 Forex algorithms simultaneously from one centralized dashboard.</p>
            </div>

        </div>
    </div>

    <!-- ABOUT US & TEAM SECTION -->
    <div class="container" id="about" style="padding: 8rem 1rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 4rem; align-items: center;">
            <div style="flex: 1; min-width: 300px;">
                <h2 style="font-size: 3rem; margin-bottom: 1.5rem;">Who <span class="text-gradient">We Are</span></h2>
                <p class="text-secondary" style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
                    Capital First was built by a group of quantitative traders and software engineers who were tired of the limitations of retail trading platforms. We believe that institutional-grade algorithmic trading should be accessible to everyone.
                </p>
                <p class="text-secondary" style="font-size: 1.1rem; line-height: 1.8;">
                    Our mission is to level the playing field by providing retail traders with the ultra-low latency infrastructure and advanced execution algorithms that were previously only available to hedge funds.
                </p>
            </div>
            <div style="flex: 1; min-width: 300px;" id="team">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <!-- Team Member 1 -->
                    <div class="glass-panel" style="text-align: center; padding: 2rem 1rem; border-radius: 16px; border: 1px solid rgba(0, 240, 255, 0.2); transition: all 0.3s ease;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple)); margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);">JD</div>
                        <h4 style="color: #fff; margin-bottom: 0.2rem;">John Doe</h4>
                        <span style="color: var(--accent-neon); font-size: 0.85rem;">Lead Quant</span>
                    </div>
                    <!-- Team Member 2 -->
                    <div class="glass-panel" style="text-align: center; padding: 2rem 1rem; border-radius: 16px; border: 1px solid rgba(39, 201, 63, 0.2); transition: all 0.3s ease;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-green), var(--accent-blue)); margin: 0 auto 1rem auto; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; box-shadow: 0 0 20px rgba(39, 201, 63, 0.2);">AS</div>
                        <h4 style="color: #fff; margin-bottom: 0.2rem;">Alice Smith</h4>
                        <span style="color: var(--accent-green); font-size: 0.85rem;">CTO</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HOW OUR ALGO WORKS -->
    <div class="container" id="algo" style="padding: 4rem 1rem 8rem 1rem;">
        <div style="text-align: center; margin-bottom: 4rem;">
            <h2 style="font-size: 3rem; margin-bottom: 1rem;">The Engine: <span style="color: #fff;">How it Works</span></h2>
            <p class="text-secondary" style="max-width: 600px; margin: 0 auto; font-size: 1.1rem;">Behind the scenes of our sub-second execution pipeline.</p>
        </div>
        
        <div style="position: relative; max-width: 800px; margin: 0 auto;">
            <!-- Connecting Line -->
            <div style="position: absolute; top: 0; bottom: 0; left: 24px; width: 2px; background: linear-gradient(to bottom, var(--accent-neon), var(--accent-purple)); z-index: 0;"></div>
            
            <div style="display: flex; gap: 2rem; margin-bottom: 3rem; position: relative; z-index: 1;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-dark); border: 2px solid var(--accent-neon); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 0 15px rgba(0, 240, 255, 0.3);"><i class="fas fa-chart-line" style="color: var(--accent-neon);"></i></div>
                <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">1. Market Data Ingestion</h4>
                    <p class="text-secondary" style="margin: 0; font-size: 0.95rem;">We consume raw WebSocket feeds directly from Binance and Delta, processing order book updates in under 5 milliseconds.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 2rem; margin-bottom: 3rem; position: relative; z-index: 1;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-dark); border: 2px solid var(--accent-purple); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 0 15px rgba(179, 136, 255, 0.3);"><i class="fas fa-brain" style="color: var(--accent-purple);"></i></div>
                <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">2. Strategy Evaluation</h4>
                    <p class="text-secondary" style="margin: 0; font-size: 0.95rem;">Our proprietary C++ microservices evaluate your configured conditions (RSI, MACD, Price Action) against the live feed instantaneously.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 2rem; position: relative; z-index: 1;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-dark); border: 2px solid var(--accent-green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 0 15px rgba(39, 201, 63, 0.3);"><i class="fas fa-rocket" style="color: var(--accent-green);"></i></div>
                <div class="glass-panel" style="flex: 1; padding: 1.5rem;">
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">3. Order Execution</h4>
                    <p class="text-secondary" style="margin: 0; font-size: 0.95rem;">If conditions are met, an API request is fired to your broker from our localized VPS servers to guarantee minimal network hop latency.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- HOW IT WORKS -->
    <div style="background: rgba(255,255,255,0.02); padding: 8rem 0; border-top: 1px solid rgba(255,255,255,0.05);" id="how-it-works">
        <div class="container">
            <div style="text-align: center; margin-bottom: 4rem;">
                <h2 style="font-size: 3rem; margin-bottom: 1rem;">Start in <span class="text-gradient">3 Easy Steps</span></h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; text-align: center;">
                <div class="step-card">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--accent-neon); color: var(--accent-neon); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 1.5rem auto; box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);">
                        1
                    </div>
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">Connect Broker</h4>
                    <p class="text-secondary">Add your secure API keys from Binance, Delta, or MetaTrader.</p>
                </div>
                <div class="step-card">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--accent-neon); color: var(--accent-neon); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 1.5rem auto; box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);">
                        2
                    </div>
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">Launch Bot</h4>
                    <p class="text-secondary">Allocate capital, set drawdown limits, and link your strategy.</p>
                </div>
                <div class="step-card">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--accent-neon); color: var(--accent-neon); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 1.5rem auto; box-shadow: 0 0 20px rgba(0, 240, 255, 0.2);">
                        3
                    </div>
                    <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">Automate Profits</h4>
                    <p class="text-secondary">Relax as the bot executes trades instantly 24/7 on your behalf.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTACT SECTION -->
    <div class="container" id="contact" style="padding: 8rem 1rem;">
        <div class="glass-panel" style="max-width: 800px; margin: 0 auto; padding: 4rem 2rem; text-align: center; border-radius: 20px;">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Get in <span class="text-gradient">Touch</span></h2>
            <p class="text-secondary" style="margin-bottom: 3rem;">Have questions about our enterprise setup or need custom algorithm integration? Drop us a message.</p>
            
            <form style="max-width: 500px; margin: 0 auto; text-align: left;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Name</label>
                    <input type="text" style="width: 100%; padding: 1rem; border-radius: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-family: inherit; transition: border-color 0.3s ease;" placeholder="Your Name" onfocus="this.style.borderColor='var(--accent-neon)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Email</label>
                    <input type="email" style="width: 100%; padding: 1rem; border-radius: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-family: inherit; transition: border-color 0.3s ease;" placeholder="you@example.com" onfocus="this.style.borderColor='var(--accent-neon)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>
                <div style="margin-bottom: 2rem;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Message</label>
                    <textarea rows="4" style="width: 100%; padding: 1rem; border-radius: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-family: inherit; resize: vertical; transition: border-color 0.3s ease;" placeholder="How can we help?" onfocus="this.style.borderColor='var(--accent-neon)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"></textarea>
                </div>
                <button type="button" class="btn btn-primary pulse-btn" style="width: 100%; padding: 1rem; font-size: 1.1rem; justify-content: center;">Send Message</button>
            </form>
            
            <div style="margin-top: 3rem; display: flex; justify-content: center; gap: 2rem;">
                <a href="#" style="color: var(--accent-neon); font-size: 1.5rem; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"><i class="fab fa-telegram"></i></a>
                <a href="#" style="color: var(--accent-neon); font-size: 1.5rem; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"><i class="fab fa-twitter"></i></a>
                <a href="#" style="color: var(--accent-neon); font-size: 1.5rem; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>

    <!-- CTA SECTION -->
    <div class="container" style="padding: 8rem 1rem; text-align: center;">
        <h2 style="font-size: 3rem; margin-bottom: 1.5rem;">Ready to automate?</h2>
        <a href="{{ route('register') }}" class="btn btn-primary pulse-btn" style="font-size: 1.25rem; padding: 1.2rem 3rem;">Create Free Account</a>
    </div>

    <!-- FOOTER -->
    <footer style="background: rgba(0,0,0,0.5); padding: 4rem 0 2rem 0; border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="container">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 2rem; margin-bottom: 3rem;">
                <div style="max-width: 300px;">
                    <div style="font-size: 1.5rem; font-weight: 900; letter-spacing: -1px; margin-bottom: 1rem;">
                        <i class="fas fa-bolt" style="color: var(--accent-neon);"></i> Capital <span style="color: var(--text-primary);">First</span>
                    </div>
                    <p class="text-secondary" style="font-size: 0.9rem;">The premier platform for automating your crypto and forex trading strategies with enterprise-grade latency.</p>
                </div>
                <div>
                    <h4 style="color: #fff; margin-bottom: 1rem;">Platform</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li><a href="#" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Features</a></li>
                        <li><a href="#" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Pricing</a></li>
                        <li><a href="#" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Documentation</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color: #fff; margin-bottom: 1rem;">Legal</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li><a href="{{ route('policy.disclaimer') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Disclaimer</a></li>
                        <li><a href="{{ route('policy.terms') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Terms and conditions</a></li>
                        <li><a href="{{ route('policy.privacy') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Privacy Policy</a></li>
                        <li><a href="{{ route('policy.refund') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem;">Refund Policy</a></li>
                    </ul>
                </div>
            </div>
            <div style="text-align: center; color: rgba(255,255,255,0.2); font-size: 0.8rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 2rem;">
                &copy; {{ date('Y') }} Capital First. All rights reserved. Trading involves significant risk of loss.
            </div>
        </div>
    </footer>

</div>
@endsection
