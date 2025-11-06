<?php
/**
 * Public Home/Landing Page
 * Features, pricing, and sign up/sign in options
 */
session_start();
require_once __DIR__ . '/functions/db.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/core.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Virtual Interview Portal - AI-Powered Recruitment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .pricing-card {
            transition: transform 0.3s ease;
        }
        .pricing-card:hover {
            transform: translateY(-10px);
        }
        .pricing-card.featured {
            border: 3px solid #667eea;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="bi bi-clipboard-check text-primary me-2"></i>
                Interview Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <?php if (is_authenticated()): ?>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="index.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary ms-2" href="gui/login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Sign In
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="gui/register.php">
                                <i class="bi bi-person-plus"></i> Get Started
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">AI-Powered Virtual Interviews</h1>
            <p class="lead mb-5">Revolutionize your hiring process with intelligent automation. Save time, reduce bias, and find the perfect candidates faster.</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="gui/register.php" class="btn btn-light btn-lg px-5">
                    <i class="bi bi-rocket-takeoff"></i> Start Free Trial
                </a>
                <a href="#how-it-works" class="btn btn-outline-light btn-lg px-5">
                    <i class="bi bi-play-circle"></i> See How It Works
                </a>
            </div>
            <div class="mt-5">
                <small class="text-white-50">✓ No credit card required  •  ✓ 14-day free trial  •  ✓ Cancel anytime</small>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Powerful Features</h2>
                <p class="lead text-muted">Everything you need to streamline your recruitment process</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-robot feature-icon"></i>
                        <h4>AI Job Descriptions</h4>
                        <p class="text-muted">Generate professional, compelling job postings in seconds using advanced AI technology.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-chat-dots feature-icon"></i>
                        <h4>Automated Interviews</h4>
                        <p class="text-muted">AI conducts text-based interviews with tailored questions for each position.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-clipboard-data feature-icon"></i>
                        <h4>Smart Evaluation</h4>
                        <p class="text-muted">Get detailed AI-powered reports with scoring and hiring recommendations.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-shield-check feature-icon"></i>
                        <h4>Self-Hosted & Secure</h4>
                        <p class="text-muted">Full control over your data with self-hosted deployment and enterprise security.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-lightning feature-icon"></i>
                        <h4>Lightning Fast</h4>
                        <p class="text-muted">Screen hundreds of candidates in minutes, not weeks. Accelerate your hiring pipeline.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Analytics Dashboard</h4>
                        <p class="text-muted">Track applications, interviews, and hiring metrics in real-time.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">How It Works</h2>
                <p class="lead text-muted">Simple, efficient, and effective</p>
            </div>
            
            <div class="row align-items-center mb-5">
                <div class="col-md-6">
                    <div class="pe-md-5">
                        <div class="d-flex align-items-start mb-4">
                            <div class="bg-primary text-white rounded-circle p-3 me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <strong>1</strong>
                            </div>
                            <div>
                                <h4>Create Job Posting</h4>
                                <p class="text-muted">Use AI to generate professional job descriptions or write your own. Set requirements and expectations.</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="bg-primary text-white rounded-circle p-3 me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <strong>2</strong>
                            </div>
                            <div>
                                <h4>Share Application Link</h4>
                                <p class="text-muted">Get a unique link for each job and share it on job boards, social media, or via email.</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="bg-primary text-white rounded-circle p-3 me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <strong>3</strong>
                            </div>
                            <div>
                                <h4>AI Conducts Interviews</h4>
                                <p class="text-muted">Candidates complete automated text-based interviews at their convenience. AI asks relevant questions.</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded-circle p-3 me-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <strong>4</strong>
                            </div>
                            <div>
                                <h4>Review & Hire</h4>
                                <p class="text-muted">Get detailed AI evaluation reports with scores and recommendations. Make informed hiring decisions.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <img src="https://via.placeholder.com/600x500/667eea/ffffff?text=Dashboard+Preview" alt="Platform Preview" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Simple, Transparent Pricing</h2>
                <p class="lead text-muted">Choose the plan that fits your needs</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <!-- Starter Plan -->
                <div class="col-md-4">
                    <div class="card pricing-card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <h4 class="card-title">Starter</h4>
                            <p class="text-muted">Perfect for small teams</p>
                            <div class="my-4">
                                <h2 class="display-4 fw-bold">$49<small class="text-muted fs-6">/month</small></h2>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Up to 10 jobs</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 100 interviews/month</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> AI job descriptions</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> AI interviews</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Basic reports</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Email support</li>
                            </ul>
                            <a href="gui/register.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                
                <!-- Professional Plan -->
                <div class="col-md-4">
                    <div class="card pricing-card featured h-100 border-0 shadow-lg">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <strong><i class="bi bi-star-fill"></i> MOST POPULAR</strong>
                        </div>
                        <div class="card-body p-4">
                            <h4 class="card-title">Professional</h4>
                            <p class="text-muted">For growing companies</p>
                            <div class="my-4">
                                <h2 class="display-4 fw-bold">$149<small class="text-muted fs-6">/month</small></h2>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Unlimited jobs</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 500 interviews/month</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Advanced AI features</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Detailed analytics</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Custom branding</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Priority support</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> API access</li>
                            </ul>
                            <a href="gui/register.php" class="btn btn-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                
                <!-- Enterprise Plan -->
                <div class="col-md-4">
                    <div class="card pricing-card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <h4 class="card-title">Enterprise</h4>
                            <p class="text-muted">For large organizations</p>
                            <div class="my-4">
                                <h2 class="display-4 fw-bold">Custom</h2>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Everything in Pro</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Unlimited interviews</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Multiple admin users</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Custom integrations</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Dedicated support</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> SLA guarantee</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> On-premise option</li>
                            </ul>
                            <a href="mailto:sales@hrportal.com" class="btn btn-outline-primary w-100">Contact Sales</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <p class="text-muted">All plans include 14-day free trial. No credit card required.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Ready to Transform Your Hiring?</h2>
            <p class="lead mb-5">Join hundreds of companies using AI to find better candidates faster</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="gui/register.php" class="btn btn-light btn-lg px-5">
                    <i class="bi bi-rocket-takeoff"></i> Get Started Free
                </a>
                <a href="gui/login.php" class="btn btn-outline-light btn-lg px-5">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">HR Virtual Interview Portal</h5>
                    <p class="text-white-50">AI-powered recruitment platform that automates interviews and streamlines hiring.</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Product</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-white-50 text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#pricing" class="text-white-50 text-decoration-none">Pricing</a></li>
                        <li class="mb-2"><a href="gui/register.php" class="text-white-50 text-decoration-none">Sign Up</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Company</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">About</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Contact</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Careers</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Resources</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="README.md" class="text-white-50 text-decoration-none">Documentation</a></li>
                        <li class="mb-2"><a href="QUICKSTART.md" class="text-white-50 text-decoration-none">Quick Start</a></li>
                        <li class="mb-2"><a href="check.php" class="text-white-50 text-decoration-none">System Check</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Privacy</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Terms</a></li>
                        <li class="mb-2"><a href="LICENSE" class="text-white-50 text-decoration-none">License</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> HR Virtual Interview Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
