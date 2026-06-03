<!DOCTYPE html>
	<html lang="zxx" class="no-js">
	<head>
		<!-- Mobile Specific Meta -->
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- Favicon-->
		<link rel="shortcut icon" href="img/log.jpg.png">
		<!-- Author Meta -->
		<meta name="author" content="codepixer">
		<!-- Meta Description -->
		<meta name="description" content="">
		<!-- Meta Keyword -->
		<meta name="keywords" content="">
		<!-- meta character set -->
		<meta charset="UTF-8">
		<!-- Site Title -->
		<title>Investhood IT Recruitment</title>

		<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
		<link href="https://fonts.googleapis.com/css?family=Poppins:100,200,400,300,500,600,700" rel="stylesheet"> 
			<!--
			CSS
			============================================= -->
			<link rel="stylesheet" href="css/linearicons.css">
			<link rel="stylesheet" href="css/font-awesome.min.css">
			<link rel="stylesheet" href="css/bootstrap.css">
			<link rel="stylesheet" href="css/magnific-popup.css">
			<link rel="stylesheet" href="css/nice-select.css">					
			<link rel="stylesheet" href="css/animate.min.css">
			<link rel="stylesheet" href="css/owl.carousel.css">
			<link rel="stylesheet" href="css/main.css">
		<style>
			/* Shared theme tokens (matched to index.php) */
			:root {
				--primary: #7C3AED;
				--primary-glow: rgba(124, 58, 237, 0.5);
				--dark: #09090B;
				--white: #FFFFFF;
				--gray-50: #FAFAFA;
				--gray-100: #F4F4F5;
				--gray-200: #E4E4E7;
				--gray-400: #A1A1AA;
				--gray-600: #6B7280;
				--gray-800: #18181B;
				--grid-main: rgba(124, 58, 237, 0.15);
				--grid-sub: rgba(124, 58, 237, 0.05);

				--primary-color: var(--primary);
				--secondary-color: #6D28D9;
				--accent-color: #F59E0B;
				--text-primary: var(--gray-800);
				--text-secondary: var(--gray-600);
				--bg-primary: var(--white);
				--bg-secondary: var(--gray-100);

				--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
				--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
				--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
				--border-radius-sm: 0.375rem;
				--border-radius-md: 0.5rem;
				--border-radius-lg: 1.5rem;
				--transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
				--transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
			}

			/* index.php background visuals */
			.bg-canvas {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				z-index: -1;
				background-color: var(--white);
				background-image:
					linear-gradient(var(--grid-main) 1.5px, transparent 1.5px),
					linear-gradient(90deg, var(--grid-main) 1.5px, transparent 1.5px),
					linear-gradient(var(--grid-sub) 1px, transparent 1px),
					linear-gradient(90deg, var(--grid-sub) 1px, transparent 1px);
				background-size: 80px 80px, 80px 80px, 20px 20px, 20px 20px;
				animation: gridMove 30s linear infinite;
			}
			.bg-glow {
				position: absolute;
				top: -10%;
				left: 50%;
				transform: translateX(-50%);
				width: 120vw;
				height: 100vh;
				background: radial-gradient(circle at 50% 30%, var(--primary-glow) 0%, transparent 60%);
				z-index: -1;
				filter: blur(60px);
				opacity: 0.7;
			}
			@keyframes gridMove {
				0% { background-position: 0 0; }
				100% { background-position: 80px 80px; }
			}


			/* Header Modernization */
			#header {
				background: transparent;
				box-shadow: var(--shadow-sm);
				position: fixed;
				top: 0;
				width: 100%;
				z-index: 1000;
				backdrop-filter: blur(10px);
				border-bottom: 1px solid rgba(255, 255, 255, 0.1);
			}

			.header-scrolled {
				background: transparent !important;
				backdrop-filter: blur(10px) !important;
			}

			.header-scrolled #nav-menu-container ul li a {
				color: #333;
				font-weight: bold;
			}

			#nav-menu-container ul li a {
				color: #fff;
				transition: color 0.3s ease;
				font-weight: bold;
			}

			#nav-menu-container ul li a:hover {
				color: #e74c3c;
			}

			.ticker-btn {
				background: #e74c3c;
				color: #fff;
				padding: 0.5rem 1rem;
				border-radius: 25px;
				transition: all 0.3s ease;
			}

			.ticker-btn:hover {
				transform: translateY(-2px);
				box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
			}

			/* Banner Area */
				.banner-area {
					min-height: 400px;
					display: flex;
					align-items: center;
				}

				.banner-area .overlay {
					background: rgba(0, 0, 0, 0.3);
				}

				.banner-area h1 {
					font-size: 3rem;
					font-weight: 400;
					text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
					animation: fadeInUp 2s ease-out;
				}
			
			/* Modern Contact Page Styling (card-on-gray like index.php) */
			.contact-page-area {
				background: var(--gray-50);
				padding: 8rem 0 4rem;
				position: relative;
				overflow: hidden;
				min-height: 100vh;
			}


			.contact-page-area::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background:
					radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
					radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
					radial-gradient(circle at 60% 40%, rgba(245, 158, 11, 0.05) 0%, transparent 50%);
			}

			.contact-page-area::after {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background-image:
					radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.8) 0%, transparent 50%),
					radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.6) 0%, transparent 50%);
				opacity: 0.6;
			}

			/* Modern Form Container */
				.form-area {
					background: rgba(255, 255, 255, 0.92);
					backdrop-filter: blur(20px) saturate(180%);
					border-radius: 32px;
					padding: 3rem;
					box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.08);
					border: 1px solid rgba(255, 255, 255, 0.25);
					transition: var(--transition-normal);
					position: relative;
					overflow: hidden;
					height: 445px;
				}


			.form-area::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				height: 4px;
				background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--accent-color));
			}

			.form-area:hover {
				transform: translateY(-8px) scale(1.02);
				box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
			}

			/* Footer */
			.footer-area {
				background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
				color: #fff;
				padding: 3rem 0;
			}

			.single-footer-widget h6 {
				color: var(--primary-color);
				margin-bottom: 1rem;
				font-weight: 600;
			}

			.footer-nav li {
				margin-bottom: 0.5rem;
			}

			.footer-nav a {
				color: #d1d5db;
				transition: var(--transition-fast);
			}

			.footer-nav a:hover {
				color: var(--primary-color);
				transform: translateX(4px);
			}

			/* Modern Input Fields */
			.common-input, .common-textarea {
				width: 100%;
				border: 2px solid #e5e7eb;
				border-radius: var(--border-radius-md);
				padding: 1rem 1.25rem;
				font-size: 1rem;
				transition: var(--transition-normal);
				background: var(--bg-secondary);
				margin-bottom: 1.5rem;
				font-family: 'Poppins', sans-serif;
				color: var(--text-primary);
				box-shadow: var(--shadow-sm);
			}

			.common-input:focus, .common-textarea:focus {
				border-color: var(--primary-color);
				box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
				background: var(--bg-primary);
				outline: none;
				transform: translateY(-2px);
			}

			.common-input::placeholder, .common-textarea::placeholder {
				color: var(--text-secondary);
				transition: var(--transition-fast);
			}

			.common-input:focus::placeholder, .common-textarea:focus::placeholder {
				color: transparent;
			}

			.common-textarea {
				resize: vertical;
				min-height: 120px;
			}

			/* Form Button */
			.form-area .primary-btn {
				background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
				border: none;
				border-radius: var(--border-radius-md);
				padding: 1rem 2rem;
				font-size: 1rem;
				font-weight: 600;
				color: #fff;
				transition: var(--transition-normal);
				box-shadow: var(--shadow-md);
				cursor: pointer;
				position: relative;
				overflow: hidden;
				width: auto;
				float: none;
			}

			.form-area .primary-btn::before {
				content: '';
				position: absolute;
				top: 0;
				left: -100%;
				width: 100%;
				height: 100%;
				background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
				transition: left 0.6s;
			}

			.form-area .primary-btn:hover::before {
				left: 100%;
			}

			.form-area .primary-btn:hover {
				transform: translateY(-2px);
				box-shadow: var(--shadow-lg);
			}

			.map-wrap {
				border-radius: var(--border-radius-lg);
				overflow: hidden;
				box-shadow: var(--shadow-lg);
				transition: var(--transition-normal);
				position: relative;
			}

			.map-wrap::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: linear-gradient(45deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
				pointer-events: none;
				z-index: 1;
			}

			.map-wrap:hover {
				transform: scale(1.03) rotate(1deg);
				box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
			}

			.map-wrap iframe {
				border-radius: var(--border-radius-lg);
				transition: var(--transition-normal);
				position: relative;
				z-index: 2;
			}

			/* Responsive Design */
			@media (max-width: 768px) {
				.contact-page-area {
					padding: 6rem 0 2rem;
				}

				.form-area {
					padding: 2rem 1.5rem;
					margin-bottom: 2rem;
				}

				.map-wrap {
					height: 300px !important;
				}

				.callto-action-area .title h1 {
					font-size: 2rem;
				}
			}

			/* Modern Animations */
			@keyframes slideInFromLeft {
				from {
					opacity: 0;
					transform: translateX(-50px);
				}
				to {
					opacity: 1;
					transform: translateX(0);
				}
			}

			@keyframes slideInFromRight {
				from {
					opacity: 0;
					transform: translateX(50px);
				}
				to {
					opacity: 1;
					transform: translateX(0);
				}
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

			.form-area {
				animation: slideInFromLeft 0.8s ease-out;
			}

			.map-wrap {
				animation: slideInFromRight 0.8s ease-out;
			}

			.form-group {
				animation: fadeInUp 0.6s ease-out;
				animation-fill-mode: both;
			}

			.form-group:nth-child(1) { animation-delay: 0.1s; }
			.form-group:nth-child(2) { animation-delay: 0.2s; }
			.form-group:nth-child(3) { animation-delay: 0.3s; }
			.form-group:nth-child(4) { animation-delay: 0.4s; }

			/* Utility Classes */
			.text-gradient {
				background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
				-webkit-background-clip: text;
				-webkit-text-fill-color: transparent;
				background-clip: text;
			}

			.glass-effect {
				background: rgba(255, 255, 255, 0.1);
				backdrop-filter: blur(10px);
				border: 1px solid rgba(255, 255, 255, 0.2);
			}
		</style>
		</head>
		<body>

		<header id="header" id="home">
			    <div class="container">
			    	<div class="row align-items-center justify-content-between d-flex">
					<div id="logo">
						<a href="index.php"><img src="img/logo1.png" alt="" title="Candidit Recruitment" style="width: 65px; height: auto;" /></a>
						<span style="font-weight: bold; margin-left: 0px; color: #ff0000; font-size: 1.2em;">Candidit</span>
                	</div>
				      <button class="mobile-nav-toggle" id="mobile-nav-toggle"><i class="fas fa-bars"></i></button>
				      <nav id="nav-menu-container">
				        <ul class="nav-menu">
				          <li class="menu-active"><a href="index.php">Home</a></li>
				          <li><a href="about.php">About Us</a></li>
				          <li><a href="jobs.php">Jobs</a></li>
						  <li><a href="contact.php">Contact</a></li>
						  <li><a href="login_signup.php">Login</a></li>			          				          
				        </ul>
				      </nav><!-- #nav-menu-container -->
					  <li><a class="ticker-btn" href="login_signup.php">Login | Register</a></li>		    		
			    	</div>
			    </div>
			  </header><!-- #header -->


			<!-- start banner Area -->
			<section class="banner-area relative" id="home">	
				<div class="overlay overlay-bg"></div>
				<div class="container">
					<div class="row d-flex align-items-center justify-content-center">
						<div class="about-content col-lg-12">
							<h1 class="text-white">
								Contact Us				
							</h1>	
							<p class="text-white"><a href="index.php">Home </a>  <span class="lnr lnr-arrow-right"></span>  <a href="contact.php"> Contact Us</a></p>
						</div>											
					</div>
				</div>
			</section>
			<!-- End banner Area -->

			<!-- Start Contact Information Area -->
			<section class="contact-info-area section-gap" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 4rem 0; position: relative;">
				<div class="container">
					<div class="row justify-content-center">
						<div class="col-lg-8">
							<div class="contact-info-container" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); border-radius: var(--border-radius-lg); padding: 3rem; box-shadow: var(--shadow-lg); border: 1px solid rgba(255, 255, 255, 0.3); text-align: center; animation: fadeInUp 1s ease-out;">
								<h2 style="color: var(--text-primary); margin-bottom: 2rem; font-weight: 700; font-size: 2.5rem;">Get In Touch</h2>
								<p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 3rem; line-height: 1.6;">We're here to help you with all your recruitment needs. Reach out to us using the information below.</p>
								<div class="row">
									<div class="col-md-4 contact-item" style="margin-bottom: 2rem;">
										<div class="contact-icon" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
											<i class='bx bx-envelope'></i>
										</div>
										<h5 style="color: var(--text-primary); margin-bottom: 0.5rem; font-weight: 600;">Email</h5>
										<p style="color: var(--text-secondary); margin: 0;"><a href="mailto:admin@investhoodit.co.za" style="color: var(--primary-color); text-decoration: none; transition: var(--transition-fast);">admin@investhoodit.co.za</a></p>
									</div>
									<div class="col-md-4 contact-item" style="margin-bottom: 2rem;">
										<div class="contact-icon" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 1rem;">
											<i class='bx bx-phone'></i>
										</div>
										<h5 style="color: var(--text-primary); margin-bottom: 0.5rem; font-weight: 600;">Phone</h5>
										<p style="color: var(--text-secondary); margin: 0;"><a href="tel:0682460562" style="color: var(--secondary-color); text-decoration: none; transition: var(--transition-fast);">068 246 0562</a></p>
									</div>
									<div class="col-md-4 contact-item" style="margin-bottom: 2rem;">
										<div class="contact-icon" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;">
											<i class='bx bx-map'></i>
										</div>
										<h5 style="color: var(--text-primary); margin-bottom: 0.5rem; font-weight: 600;">Address</h5>
										<p style="color: var(--text-secondary); margin: 0; line-height: 1.4;">136 2nd St, Randjespark,<br>Johannesburg, 1685</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<!-- End Contact Information Area -->

			<!-- Start contact-page Area -->
			<section class="contact-page-area section-gap">
				<div class="container">
					<div class="row">
						<div class="col-lg-8">
							<form class="form-area" id="myForm" action="contact_messages.php" method="POST" class="contact-form text-right">
								<div class="row">    
									<div class="col-lg-12 form-group">
										<input name="fullname" placeholder="Enter your name and surname" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter your name and surname'" class="common-input mb-20 form-control" required="" type="text">
										
										<input name="email" placeholder="Enter email address" pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter email address'" class="common-input mb-20 form-control" required="" type="email">

										<input name="subject" placeholder="Enter your subject" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter your subject'" class="common-input mb-20 form-control" required="" type="text">
										<textarea class="common-textarea mt-10 form-control" name="message" placeholder="Message" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Message'" required=""></textarea>
										<button type="submit" class="primary-btn mt-20 text-blue" style="float: left;">Send Message</button>
									</div>
								</div>
							</form>    
						</div>
						<div class="col-lg-4">
							<div class="map-wrap" style="width:100%; height: 445px;">
								<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3581.679385043763!2d28.1219652!3d-25.9889752!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1e956e178381e3b9%3A0x1cb0aca2fa587590!2s136%202nd%20St%2C%20Randjespark%2C%20Midrand%2C%201685!5e0!3m2!1sen!2sza!4v1694255193184!5m2!1sen!2sza" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
							</div>
						</div>
					</div>
				</div>    
			</section>
			<!-- End contact-page Area -->	

			<!-- start footer Area -->
			<footer class="footer-area section-gap">
				<div class="container">
					<div class="row d-flex justify-content-between">
						<div class="col-lg-3 col-md-12">
							<div class="single-footer-widget">
								<h6><span style="color: #fff;">Contact Information</h6>
								<p><strong><i class='bx bx-envelope'></i></strong> <span id="email-display">admin@investhoodit.co.za</span></p>
								<p><strong><i class='bx bx-phone'></i></strong> <span id="phone-display">068 246 0562</span></p>
								<p><strong><i class='bx bx-map'></i></strong> <span id="address-display">136 2nd St, Randjespark, Johannesburg, 1685</span></p>
							</div>
						</div>
						<div class="col-lg-6 col-md-12">
							<div class="single-footer-widget newsletter">
								<h6>About Us</h6>
								<p>
									We are dedicated to connecting talented job seekers with top employers, creating opportunities for career growth and business success.
									Our platform is designed to simplify the hiring process by offering a seamless experience for both candidates and recruiters. Whether you're an individual 
									looking for your next career move or a company searching for the perfect candidate, we provide tailored recruitment solutions to meet your unique needs.
									With a user-friendly interface, advanced job-matching technology, and expert support, we ensure a smooth and efficient hiring journey.
								</p>
							</div>
						</div>
						<div class="col-lg-3 col-md-12" style="display: flex; justify-content: flex-end;">
							<div class="single-footer-widget">
								<h6>Quick Links</h6>
								<ul class="footer-nav">
									<li><a href="index.php">Home</a></li>
									<li><a href="about.php">About Us</a></li>
									<li><a href="jobs.php">Opportunities</a></li>
									<li><a href="contact.php">Contact Us</a></li>
									<li><a href="#">Privacy Policy</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</footer>
			<!-- End footer Area -->		

			<script src="js/vendor/jquery-2.2.4.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
			<script src="js/vendor/bootstrap.min.js"></script>			
			<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBhOdIF3Y9382fqJYt5I_sswSrEw5eihAA"></script>
  			<script src="js/easing.min.js"></script>			
			<script src="js/hoverIntent.js"></script>
			<script src="js/superfish.min.js"></script>	
			<script src="js/jquery.ajaxchimp.min.js"></script>
			<script src="js/jquery.magnific-popup.min.js"></script>	
			<script src="js/owl.carousel.min.js"></script>			
			<script src="js/jquery.sticky.js"></script>
			<script src="js/jquery.nice-select.min.js"></script>			
			<script src="js/parallax.min.js"></script>		
			<script src="js/mail-script.js"></script>
			<script src="js/main.js"></script>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const mobileNavToggle = document.getElementById('mobile-nav-toggle');
					const navMenuContainer = document.getElementById('nav-menu-container');

					mobileNavToggle.addEventListener('click', function() {
						navMenuContainer.classList.toggle('active');
					});
				});
			</script>
		</body>
	</html>



