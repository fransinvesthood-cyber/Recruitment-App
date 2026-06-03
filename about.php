<!DOCTYPE html>
	<html lang="zxx" class="no-js">
	<head>
		<!-- Mobile Specific Meta -->
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- Favicon-->
		<link rel="shortcut icon" href="img/fav.png">
		<!-- Author Meta -->
		<meta name="author" content="codepixer">
		<!-- Meta Description -->
		<meta name="description" content="">
		<!-- Meta Keyword -->
		<meta name="keywords" content="">
		<!-- meta character set -->
		<meta charset="UTF-8">
		<!-- Site Title -->
		<title>About Us</title>

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
				:root {
					--primary-color: #ff6b6b;
					--secondary-color: #4ecdc4;
					--accent-color: #45b7d1;
					--dark-color: #2c3e50;
					--light-color: #ecf0f1;
					--shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
					--border-radius: 15px;
					--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
				}

				body {
					font-family: 'Poppins', sans-serif;
					line-height: 1.6;
					color: var(--dark-color);
				}

				p {
					font-weight: 500;
				}

				/* Header Modernization */
				#header {
					background: transparent;
					box-shadow: var(--shadow-sm);
					backdrop-filter: blur(10px);
					position: fixed;
					top: 0;
					width: 100%;
					z-index: 1000;
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
					background: rgba(0, 0, 0, 0.4);
				}

				.banner-area h1 {
					font-size: 3rem;
					font-weight: 400;
					text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
					animation: fadeInUp 2s ease-out;
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

				/* Service Area */
				.service-area {
					padding: 80px 0;
					background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
				}

				.service-area h1 {
					font-size: 2.5rem;
					font-weight: 600;
					margin-bottom: 20px;
					color: var(--dark-color);
				}

				.single-service {
					background: white;
					padding: 40px 30px;
					margin-bottom: 30px;
					border-radius: var(--border-radius);
					box-shadow: var(--shadow);
					transition: var(--transition);
					border: 1px solid transparent;
					position: relative;
					overflow: hidden;
					min-height: 280px;
					display: flex;
					flex-direction: column;
					justify-content: space-between;
				}

				.single-service::before {
					content: '';
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 4px;
					background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
					transform: scaleX(0);
					transition: var(--transition);
				}

				.single-service:hover {
					transform: translateY(-10px);
					box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
					cursor: pointer;
				}

				.single-service:hover::before {
					transform: scaleX(1);
				}

				.single-service h4 {
					font-size: 1.25rem;
					font-weight: 600;
					margin-top: 20px;
					margin-bottom: 20px;
					color: var(--dark-color);
					display: flex;
					align-items: center;
				}

				.single-service h4 .fas {
					margin-right: 15px;
					color: var(--primary-color);
					font-size: 1.5rem;
				}

				.single-service p {
					color: #666;
					line-height: 1.7;
				}

				/* Feature Area */
				.feature-area {
					background: linear-gradient(135deg, var(--dark-color), #34495e);
					color: white;
					padding: 40px 0;
				}

				.feat-img img {
					width: 100%;
					height: 300px;
					object-fit: cover;
					border-radius: var(--border-radius);
					box-shadow: var(--shadow);
				}

				.feat-txt h6 {
					font-size: 1.2rem;
					font-weight: 600;
					margin-bottom: 20px;
					color: var(--secondary-color);
				}

				.feat-txt p {
					font-size: 14px;
					line-height: 1.8;
				}

				/* Team Area */
				.team-area {
					padding: 80px 0;
					background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
				}

				.team-area h1 {
					font-size: 2.5rem;
					font-weight: 600;
					color: var(--dark-color);
				}

				.single-team {
					margin-bottom: 40px;
					transition: var(--transition);
				}

				.single-team:hover {
					transform: translateY(-5px);
				}

				.single-team .thumb {
					position: relative;
					border-radius: var(--border-radius);
					overflow: hidden;
					box-shadow: var(--shadow);
				}

				.single-team .thumb img {
					width: 100%;
					height: 250px;
					object-fit: cover;
					transition: var(--transition);
				}

				.single-team:hover .thumb img {
					transform: scale(1.05);
				}

				.single-team .thumb .align-items-center {
					position: absolute;
					bottom: 0;
					left: 0;
					right: 0;
					background: rgba(0, 0, 0, 0.7);
					padding: 10px;
					opacity: 0;
					transition: var(--transition);
				}

				.single-team:hover .thumb .align-items-center {
					opacity: 1;
				}

				.single-team .thumb .align-items-center a {
					color: white;
					margin: 0 10px;
					font-size: 1.2rem;
					transition: var(--transition);
				}

				.single-team .thumb .align-items-center a:hover {
					color: var(--secondary-color);
				}

				.meta-text h4 {
					font-size: 1.25rem;
					font-weight: 600;
					color: var(--dark-color);
				}

				.meta-text p {
					color: var(--primary-color);
					font-weight: 500;
				}

				/* Testimonial Area */
				.testimonial-area {
					padding: 80px 0;
					background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
				}

				.testimonial-area h1 {
					font-size: 2.5rem;
					font-weight: 600;
					color: var(--dark-color);
				}

				.single-review {
					background: white;
					padding: 30px;
					border-radius: var(--border-radius);
					box-shadow: var(--shadow);
					margin: 20px 0;
					transition: var(--transition);
				}

				.single-review:hover {
					transform: translateY(-5px);
					box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
				}

				.single-review img {
					width: 60px;
					height: 60px;
					border-radius: 50%;
					margin-bottom: 15px;
				}

				.single-review .title h4 {
					font-size: 1.1rem;
					font-weight: 600;
					color: var(--dark-color);
				}

				.star .fa-star {
					color: #ddd;
				}

				.star .fa-star.checked {
					color: #ffc107;
				}

				/* Footer */
				.footer-area {
					background: var(--dark-color);
					color: white;
					padding: 60px 0 30px;
				}

				.footer-area h6 {
					font-size: 1.25rem;
					font-weight: 600;
					margin-bottom: 20px;
					color: var(--secondary-color);
				}

				.footer-nav li {
					margin-bottom: 10px;
				}

				.footer-nav li a {
					color: #ccc;
					text-decoration: none;
					transition: var(--transition);
				}

				.footer-nav li a:hover {
					color: var(--secondary-color);
				}

				/* Mobile Nav Toggle */
				.mobile-nav-toggle {
					display: none;
					background: none;
					border: none;
					color: #fff;
					font-size: 1.5rem;
					font-weight: normal;
					cursor: pointer;
					padding: 0.5rem;
					transition: color 0.3s ease;
				}

				.mobile-nav-toggle:hover {
					color: #e74c3c;
				}

				/* Responsive Design */
				@media (max-width: 768px) {
					.banner-area h1 {
						font-size: 2rem;
					}

					.service-area h1,
					.team-area h1,
					.testimonial-area h1,
					.callto-action-area h1 {
						font-size: 2rem;
					}

					.single-service {
						padding: 30px 20px;
					}

					.primary-btn {
						display: block;
						margin: 10px 0;
						text-align: center;
					}

					.mobile-nav-toggle {
						display: block;
					}

					#nav-menu-container {
						display: none;
						position: absolute;
						top: 100%;
						left: 0;
						width: 100%;
						background: rgba(0, 0, 0, 0.9);
						backdrop-filter: blur(10px);
						flex-direction: column;
						padding: 1rem 0;
					}

					#nav-menu-container.active {
						display: flex;
					}

					#nav-menu-container ul {
						flex-direction: column;
						width: 100%;
					}

					#nav-menu-container ul li {
						text-align: center;
						margin: 0.5rem 0;
					}

					#nav-menu-container ul li a {
						padding: 0.5rem 1rem;
						display: block;
					}
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
								About Us
							</h1>
							<p class="text-white link-nav"><a href="index.php">Home </a>  <span class="lnr lnr-arrow-right"></span>  <a href="about-us.html"> About Us</a></p>
						</div>
					</div>
				</div>
			</section>
			<!-- End banner Area -->	
				
			<!-- Start service Area -->
			<section class="service-area section-gap" id="service">
				<div class="container">
					<div class="row d-flex justify-content-center">
						<div class="col-md-8 pb-40 header-text">
							<h1>Why Choose Us?</h1>
							<p>
								At Candidit Recruitment, we are dedicated to connecting talented job seekers with top employers, creating opportunities for career growth and business success.
								Our platform is designed to simplify the hiring process by offering a seamless experience for both candidates and recruiters. Whether you're an individual 
								looking for your next career move or a company searching for the perfect candidate, we provide tailored recruitment solutions to meet your unique needs. 
								With a user-friendly interface, advanced job-matching technology, and expert support, we ensure a smooth and efficient hiring journey.
							</p>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-user"></span>Profile & Portfolio Management</h4>
								<p>
									Applicants can quickly create profiles and upload resumes to apply for jobs with just a few clicks.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-file-alt"></span>Resume Extraction & Auto-Fill</h4>
								<p>
									Uploaded resumes are automatically analyzed to extract skills, education, and experience, helping applicants build profiles faster.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-bell"></span>Job Alerts & Notifications</h4>
								<p>
									Stay ahead of new job opportunities by receiving alerts via email or SMS, customized to your preferences.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-briefcase"></span>Job Listings & Applications</h4>
								<p>
									Easily browse and apply for jobs across various industries, from entry-level to executive roles.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-search"></span>Job Search & Filtering</h4>
								<p>
									Advanced search tools to find the perfect job match based on skills, location, and preferences.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-calendar-alt"></span>Interview Scheduling</h4>
								<p>
									Applicants can view, accept, or request changes to interview appointments directly through their dashboards.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-shield-alt"></span>Background Checks & Verification</h4>
								<p>
									Ensure that your hires meet your expectations with our comprehensive background screening and reference checks.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-chart-line"></span>Applicant Tracking System (ATS)</h4>
								<p>
									Recruiters can efficiently manage applications, track candidate progress, and communicate seamlessly.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-users"></span>Interview Coordination</h4>
								<p>
									Admins can schedule, reschedule, or cancel interviews and automatically notify applicants via email or calendar integration.
								</p>
							</div>
						</div>
						<div class="col-lg-4 col-md-6">
							<div class="single-service">
								<h4><span class="fas fa-graduation-cap"></span>Training & Task Management</h4>
								<p>
									Includes modules for assigning training, managing timesheets, and tracking consultant performance.
								</p>
							</div>
						</div>
					</div>
				</div>	
			</section>
			<!-- End service Area -->						

			<!-- Start feature Area -->
			<section class="feature-area">
				<div class="container-fluid">
					<div class="row justify-content-center align-items-center">
						<div class="col-lg-3 feat-img no-padding">
						<img class="img-fluid" src="img/pic1.jpg" alt="">
								
						</div>
						<div class="col-lg-3 no-padding feat-txt">
							<h6 class="text-uppercase text-white"> Empowering Job-Seekers</h6><br>
							<p>
								We're dedicated to empowering job seekers with cutting-edge opportunities, leveraging advanced matching technology to unlock their career potential and drive professional success.
							</p>
						</div>
						<div class="col-lg-3 feat-img no-padding">
							<img class="img-fluid" src="img/pic2.jpg" alt="">
						</div>
						<div class="col-lg-3 no-padding feat-txt">
							<h6 class="text-uppercase text-white"> Partnering with Employers</h6><br>
							<p>
								We partner with forward-thinking businesses to rapidly identify top talent. Utilizing innovative recruitment tools, we optimize hiring processes and forge strong connections between companies and job-seekers.
							</p>
						</div>
					</div>
				</div>	
			</section>
			<!-- End feature Area -->

			<!-- Start team Area -->
			<section class="team-area section-gap" id="team">
				<div class="container">
					<div class="row d-flex justify-content-center">
						<div class="menu-content pb-70 col-lg-8">
							<div class="title text-center">
								<h1 class="mb-10">Our Team</h1>
								<p>
									Team-oriented collaborators, committed to supporting each other, 
									sharing knowledge, and working together to achieve common goals while 
									continuously improving skills.
								</p>
							</div>
						</div>
					</div>						
					<div class="row justify-content-center d-flex align-items-center">
						<div class="col-md-3 single-team">
							<div class="thumb">
								<img class="img-fluid" src="img/pic2.jpg" alt="" style="margin-bottom: -5mm;">
								<div class="align-items-center justify-content-center d-flex">
									<a href="#"><i class="fa fa-facebook"></i></a>
									<a href="#"><i class="fa fa-twitter"></i></a>
									<a href="#"><i class="fa fa-linkedin"></i></a>
								</div>
							</div>
							<div class="meta-text mt-30 text-center">
								<h4>Sifiso Mazibuko</h4>
								<p>Software Developer</p>									    	
							</div>
						</div>
						<div class="col-md-3 single-team">
							<div class="thumb">
								<img class="img-fluid" src="img/pic4.jpg" alt="" style="margin-bottom: -5mm;">
								<div class="align-items-center justify-content-center d-flex">
									<a href="#"><i class="fa fa-facebook"></i></a>
									<a href="#"><i class="fa fa-twitter"></i></a>
									<a href="#"><i class="fa fa-linkedin"></i></a>
								</div>
							</div>
							<div class="meta-text mt-30 text-center">
								<h4>Delani Sibande</h4>
								<p>Software Developer</p>									    	
							</div>
						</div>
						<div class="col-md-3 single-team">
							<div class="thumb">
								<img class="img-fluid" src="img/pic2.jpg" alt="" style="margin-bottom: -5mm;">
								<div class="align-items-center justify-content-center d-flex">
									<a href="#"><i class="fa fa-facebook"></i></a>
									<a href="#"><i class="fa fa-twitter"></i></a>
									<a href="#"><i class="fa fa-linkedin"></i></a>
								</div>
							</div>
							<div class="meta-text mt-30 text-center">
								<h4>Show Shipalana</h4>
								<p>Software Developer</p>			    	
							</div>
						</div>
						<div class="col-md-3 single-team">
							<div class="thumb">
								<img class="img-fluid" src="img/pic4.jpg" alt="" style="margin-bottom: -5mm;">
								<div class="align-items-center justify-content-center d-flex">
									<a href="#"><i class="fa fa-facebook"></i></a>
									<a href="#"><i class="fa fa-twitter"></i></a>
									<a href="#"><i class="fa fa-linkedin"></i></a>
								</div>
							</div>
							<div class="meta-text mt-30 text-center">
								<h4>Obakeng Masopa</h4>
								<p>Software Developer</p>									    	
							</div>
						</div>																											
					</div>
				</div>	
			</section>
			<!-- End team Area -->	

				<!-- Start testimonial Area -->
				<section class="testimonial-area section-gap" id="review">
				<div class="container">
					<div class="row d-flex justify-content-center">
						<div class="menu-content pb-60 col-lg-8">
							<div class="title text-center">
								<h1 class="mb-10">Testimonial from our Clients</h1>
								<p>
									Hear what our community has to say - real stories, real impact.</p>
							</div
							>
						</div>
					</div>						
					<div class="row">
						<div class="active-review-carusel">
							<div class="single-review">
								<img src="img/ta2.png" alt="">
								<div class="title d-flex flex-row">
									<h4>Thato Aphane (HR Manager)</h4>
									<div class="star">
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star"></span>								
									</div>
								</div>
								<p>
									Finding the right candidate used to take months, but with Investhood IT Recruitment, we filled our positions in record time!
								</p>
							</div>	
							<div class="single-review">
								<img src="img/dc2.png" alt="">
								<div class="title d-flex flex-row">
									<h4>Deco Coder (Software Developer)</h4>
									<div class="star">
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star "></span>								
									</div>
								</div>
								<p>
								Thanks to Investhood IT Recruitment, I landed my dream job in just a few weeks! The job alerts and interview tips were incredibly helpful.
								</p>
							</div>	
							<div class="single-review">
								<img src="img/bh2.png" alt="">
								<div class="title d-flex flex-row">
									<h4>Busisiwe Heavens (Recruitment Lead)</h4>
									<div class="star">
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star"></span>
										<span class="fa fa-star"></span>								
									</div>
								</div>
								<p>
									The applicant tracking system made it easy to manage and communicate with candidates. A game-changer for our hiring process!
								</p>
							</div>	
							<div class="single-review">
								<img src="img/dk2.png" alt="">
								<div class="title d-flex flex-row">
									<h4>Don Kabron (Graphic Designer)</h4>
									<div class="star">
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star checked"></span>
										<span class="fa fa-star"></span>
										<span class="fa fa-star"></span>
										<span class="fa fa-star"></span>								
									</div>
								</div>
								<p>
									I struggled to get noticed before, but after using the job-matching features, I finally got hired!
								</p>
							</div>								
						</div>
					</div>
				</div>	
			</section>
			<!-- End testimonial Area -->
			
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
				$(document).ready(function() {
					$('#mobile-nav-toggle').click(function() {
						$('#nav-menu-container').toggleClass('active');
					});
				});
			</script>
		</body>
	</html>



