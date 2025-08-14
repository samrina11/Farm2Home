<?php
require_once 'config/database.php';
require_once 'config/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Farmers Marketplace</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        /* Header Styles */
        .header {
            background: #2d5a27;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-menu a:hover {
            color: #a8d5a3;
        }
        
        .user-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45a049;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #2d5a27;
        }
        
        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-title {
            font-size: 36px;
            color: #2d5a27;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-bottom: 50px;
        }
        
        /* Hero Section */
        .hero-section {
            background: white;
            padding: 60px 40px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .hero-section h2 {
            color: #2d5a27;
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .hero-section p {
            font-size: 18px;
            color: #666;
            max-width: 800px;
            margin: 0 auto 30px;
        }
        
        /* Features Grid */
        .features-section {
            margin-bottom: 50px;
        }
        
        .section-title {
            font-size: 28px;
            color: #2d5a27;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #2d5a27;
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Mission Section */
        .mission-section {
            background: white;
            padding: 50px 40px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .mission-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        
        .mission-text h2 {
            color: #2d5a27;
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .mission-text p {
            color: #666;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .mission-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-box h3 {
            color: #2d5a27;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-box p {
            color: #666;
            font-size: 14px;
        }
        
        /* Team Section */
        .team-section {
            background: white;
            padding: 50px 40px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .team-member {
            text-align: center;
        }
        
        .member-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e8f5e8;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .team-member h3 {
            color: #2d5a27;
            margin-bottom: 10px;
        }
        
        .team-member .role {
            color: #4CAF50;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .team-member p {
            color: #666;
            font-size: 14px;
        }
        
        /* Contact Section */
        .contact-section {
            background: #2d5a27;
            color: white;
            padding: 50px 40px;
            border-radius: 15px;
            text-align: center;
        }
        
        .contact-section h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .contact-section p {
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .contact-item {
            text-align: center;
        }
        
        .contact-item h4 {
            margin-bottom: 10px;
            color: #a8d5a3;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .hero-section {
                padding: 40px 20px;
            }
            
            .mission-content {
                grid-template-columns: 1fr;
            }
            
            .mission-stats {
                grid-template-columns: 1fr;
            }
            
            .features-grid,
            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">🌱 Farmers Market</div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="about.php">About</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo htmlspecialchars(getUserName()); ?>!</span>
                    <a href="cart.php" class="btn btn-outline">Cart</a>
                    <a href="profile.php" class="btn btn-primary">Profile</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <h1 class="page-title">About Farmers Marketplace</h1>
        <p class="page-subtitle">Connecting farmers directly with consumers for fresh, quality produce</p>
        
        <!-- Hero Section -->
        <div class="hero-section">
            <h2>🌱 Fresh from Farm to Your Table</h2>
            <p>
                We are a dedicated platform that bridges the gap between local farmers and consumers, 
                ensuring you get the freshest produce while supporting sustainable agriculture and 
                empowering farming communities across the region.
            </p>
            <a href="products.php" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px;">
                Shop Fresh Products
            </a>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <h2 class="section-title">Why Choose Farmers Marketplace?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌿</div>
                    <h3>100% Fresh & Organic</h3>
                    <p>All our products are sourced directly from certified organic farms, ensuring maximum freshness and nutritional value for your family.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚚</div>
                    <h3>Direct from Farmers</h3>
                    <p>We eliminate middlemen, connecting you directly with local farmers to ensure fair prices and the freshest produce possible.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💚</div>
                    <h3>Supporting Local Communities</h3>
                    <p>Every purchase supports local farming communities and promotes sustainable agricultural practices in our region.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Easy Online Shopping</h3>
                    <p>Browse, select, and order fresh produce from the comfort of your home with our user-friendly online platform.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast Delivery</h3>
                    <p>Quick and reliable delivery service ensures your fresh produce reaches you at peak quality and freshness.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Quality Guaranteed</h3>
                    <p>We stand behind the quality of our products with a satisfaction guarantee and easy return policy.</p>
                </div>
            </div>
        </div>

        <!-- Mission Section -->
        <div class="mission-section">
            <div class="mission-content">
                <div class="mission-text">
                    <h2>Our Mission</h2>
                    <p>
                        To revolutionize the way people access fresh, healthy produce by creating a 
                        sustainable marketplace that benefits both consumers and farmers.
                    </p>
                    <p>
                        We believe in transparency, quality, and community. Our platform provides 
                        detailed information about each farmer, their practices, and the journey 
                        of your food from farm to table.
                    </p>
                    <p>
                        By choosing Farmers Marketplace, you're not just buying groceries – you're 
                        investing in sustainable agriculture, supporting local communities, and 
                        ensuring a healthier future for generations to come.
                    </p>
                </div>
                <div class="mission-stats">
                    <div class="stat-box">
                        <h3>500+</h3>
                        <p>Partner Farmers</p>
                    </div>
                    <div class="stat-box">
                        <h3>10,000+</h3>
                        <p>Happy Customers</p>
                    </div>
                    <div class="stat-box">
                        <h3>50+</h3>
                        <p>Product Varieties</p>
                    </div>
                    <div class="stat-box">
                        <h3>24/7</h3>
                        <p>Customer Support</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="team-section">
            <h2 class="section-title">Meet Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">👨‍💼</div>
                    <h3>Rajesh Kumar</h3>
                    <div class="role">Founder & CEO</div>
                    <p>Passionate about sustainable agriculture and connecting farmers with consumers.</p>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👩‍💻</div>
                    <h3>Priya Sharma</h3>
                    <div class="role">Head of Operations</div>
                    <p>Ensures smooth operations and maintains quality standards across all processes.</p>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👨‍🌾</div>
                    <h3>Amit Patel</h3>
                    <div class="role">Farmer Relations Manager</div>
                    <p>Works closely with farmers to maintain quality and build strong partnerships.</p>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👩‍🎓</div>
                    <h3>Sneha Reddy</h3>
                    <div class="role">Quality Assurance Lead</div>
                    <p>Ensures all products meet our high standards for freshness and quality.</p>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <h2>Get in Touch</h2>
            <p>Have questions or want to learn more about our mission? We'd love to hear from you!</p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <h4>📧 Email</h4>
                    <p>info@farmersmarketplace.com</p>
                </div>
                <div class="contact-item">
                    <h4>📞 Phone</h4>
                    <p>+91 98765 43210</p>
                </div>
                <div class="contact-item">
                    <h4>📍 Address</h4>
                    <p>123 Green Valley Road<br>Agricultural District, State 560001</p>
                </div>
                <div class="contact-item">
                    <h4>🕒 Support Hours</h4>
                    <p>Monday - Sunday<br>6:00 AM - 10:00 PM</p>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="products.php" class="btn btn-primary" style="background: white; color: #2d5a27;">
                    Start Shopping Now
                </a>
            </div>
        </div>
    </main>
</body>
</html>
