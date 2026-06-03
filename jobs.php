<?php
include('config.php');

//Define the base query
$query = "SELECT job_postings.*, companies.company_name, departments.department_name
          FROM job_postings
          LEFT JOIN companies ON job_postings.company_id = companies.company_id
          LEFT JOIN departments ON job_postings.department_id = departments.department_id
          WHERE 1";

//Apply search filters if any
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query .= " AND (job_postings.position LIKE '%$search%' 
                      OR job_postings.location LIKE '%$search%' 
                      OR companies.company_name LIKE '%$search%' 
                      OR departments.department_name LIKE '%$search%')";
}

$result = $conn->query($query);
?>

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
		<title>Job Listing</title>
		<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
		<link href="https://fonts.googleapis.com/css?family=Poppins:100,200,400,300,500,600,700" rel="stylesheet">
			<!--
			CSS
			============================================= -->
			<link rel="stylesheet" href="css/linearicons.css">
			<link rel="stylesheet" href="css/font-awesome.min.css">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
			<link rel="stylesheet" href="css/bootstrap.css">
			<link rel="stylesheet" href="css/magnific-popup.css">
			<link rel="stylesheet" href="css/nice-select.css">					
			<link rel="stylesheet" href="css/animate.min.css">
			<link rel="stylesheet" href="css/owl.carousel.css">
			<link rel="stylesheet" href="css/main.css">

		<style>
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
					background: rgba(0, 0, 0, 0.3);
				}

				.banner-area h1 {
					font-size: 3rem;
					font-weight: 400;
					text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
					animation: fadeInUp 2s ease-out;
				}

			/* Modern Reset and Base Styles */
			* {
				box-sizing: border-box;
			}

			body {
				margin: 0;
				padding: 0;
				font-family: 'Poppins', sans-serif;
				line-height: 1.6;
				color: #333;
				background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
				min-height: 100vh;
			}

			h1, h2, h3, h4, h5, h6 {
				margin: 0;
				padding: 0;
				font-weight: 600;
			}

			p {
				margin: 0 0 1rem 0;
				font-weight: 400;
			}

			/* Search Form Styling */
			#job-postings-container form {
				display: flex;
				justify-content: flex-start;
				align-items: center;
				margin-bottom: 2rem;
				flex-wrap: wrap;
				gap: 1rem;
				position: relative;
				padding: 0 1rem;
			}

			#job-postings-container label {
				font-size: 0.5rem;
				font-weight: 500;
				color: #2c3e50;
			}

			#job-postings-container .input-container {
				position: relative;
				width: 100%;
			}

			#job-postings-container .search-btn {
				position: absolute;
				right: 15px;
				top: 50%;
				transform: translateY(-50%);
				background: none;
				border: none;
				color: #3498db;
				font-size: 1.1rem;
				cursor: pointer;
				padding: 0;
				transition: color 0.3s ease;
			}

			#job-postings-container .search-btn:hover {
				color: #2980b9;
			}

			#job-postings-container input[type="text"] {
				padding: 0.75rem 3rem 0.75rem 1rem;
				width: 100%;
				border: 2px solid transparent;
				border-radius: 8px;
				font-size: 0.9rem;
				font-weight: 500;
				outline: none;
				transition: all 0.4s ease;
				background: #f8f9fa;
				box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
			}

			#job-postings-container input[type="text"]::placeholder {
				font-weight: bold;
			}

			#job-postings-container input[type="text"]:focus {
				border-color: #3498db;
				box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2), inset 0 2px 4px rgba(0,0,0,0.1);
				transform: translateY(-2px);
				background: #fff;
			}

			/* Footer */
			.footer-area {
				background: #2c3e50;
				color: #fff;
				padding: 3rem 0;
			}

			.single-footer-widget h6 {
				color: #3498db;
				margin-bottom: 1rem;
			}

			.footer-nav li {
				margin-bottom: 0.5rem;
			}

			.footer-nav a {
				color: #bdc3c7;
				transition: color 0.3s ease;
			}

			.footer-nav a:hover {
				color: #3498db;
			}

			/* Job Listings Container */
			#job-results {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
				gap: 1rem;
				padding: 0 1rem;
			}

			.job-listing {
				background: #fff;
				border-radius: 15px;
				padding: 2rem;
				box-shadow: 0 10px 30px rgba(0,0,0,0.1);
				transition: all 0.3s ease;
				border: 1px solid #e1e8ed;
				position: relative;
				overflow: hidden;
			}

			.job-listing::before {
				content: '';
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 5px;
				background: #34495e;
			}

			.job-listing:hover {
				transform: translateY(-10px) scale(1.02);
				box-shadow: 0 20px 40px rgba(0,0,0,0.15);
			}

			.job-listing h4 {
				font-size: 1.1rem;
				color: #7f8c8d;
				margin-bottom: 0.5rem;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			.job-listing .title {
				font-size: 1.5rem;
				color: #2c3e50;
				margin-bottom: 1rem;
				font-weight: 700;
			}

			.job-listing p {
				margin-bottom: 0.75rem;
				color: #555;
			}

			.job-listing strong {
				color: #34495e;
				font-weight: 600;
			}

			.job-listing em {
				font-style: italic;
				color: #95a5a6;
			}

			/* Buttons */
			.job-listing button, #load-more {
				background: #e74c3c;
				color: #fff;
				border: none;
				padding: 0.75rem 1.5rem;
				font-size: 0.9rem;
				font-weight: 500;
				border-radius: 8px;
				cursor: pointer;
				transition: all 0.3s ease;
				box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
				margin-top: 1rem;
				margin-right: 0.5rem;
			}

			.job-listing button:last-child {
				margin-right: 0;
			}

			.job-listing button:hover, #load-more:hover {
				background-color: #34495e;
				transform: translateY(-2px);
				box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
				color: #fff;
			}

			#load-more {
				display: block;
				margin: 2rem auto;
				width: auto;
			}

			#load-more a {
				color: inherit;
				text-decoration: none;
			}

			a {
				color: inherit;
				text-decoration: none;
			}

			/* No results message */
			.no-results {
				text-align: center;
				font-size: 1.5rem;
				color: #e74c3c;
				margin-top: 2rem;
				grid-column: 1 / -1;
				background: #fff;
				padding: 2rem;
				border-radius: 15px;
				box-shadow: 0 10px 30px rgba(0,0,0,0.1);
			}

			.job-cities {
				font-size: 0.9rem;
				line-height: 1.5;
				color: #7f8c8d;
			}

			/* Responsive Design */
			@media (max-width: 768px) {
				#job-results {
					grid-template-columns: 1fr;
					padding: 0;
				}

				.job-listing {
					padding: 1.5rem;
				}

				input[type="text"] {
					max-width: 100%;
				}

				#job-postings-container form {
					flex-direction: column;
					align-items: stretch;
				}

				/* Mobile Navigation */
				#nav-menu-container {
					position: fixed;
					top: 100%;
					left: 0;
					width: 100%;
					background: rgba(255, 255, 255, 0.95);
					backdrop-filter: blur(10px);
					transform: translateY(-100%);
					opacity: 0;
					visibility: hidden;
					transition: all 0.3s ease;
					z-index: 999;
				}

				#nav-menu-container.active {
					transform: translateY(0);
					opacity: 1;
					visibility: visible;
				}

				#nav-menu-container ul {
					flex-direction: column;
					align-items: center;
					padding: 2rem 0;
					margin: 0;
				}

				#nav-menu-container ul li {
					margin: 1rem 0;
				}

				#nav-menu-container ul li a {
					color: #333;
					font-size: 1.2rem;
					font-weight: bold;
				}

				#nav-menu-container ul li a:hover {
					color: #e74c3c;
				}

				.mobile-nav-toggle {
					display: block;
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

				.ticker-btn {
					display: none;
				}
			}

			/* Feature Categories */
			.single-fcat {
				background: rgba(255, 255, 255, 0.9);
				border-radius: 15px;
				padding: 2rem;
				text-align: center;
				transition: all 0.3s ease;
				margin-bottom: 2rem;
				min-height: 250px;
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
			}

			.single-fcat:hover {
				transform: translateY(-10px);
				box-shadow: 0 20px 40px rgba(52, 73, 94, 0.2);
			}

			.single-fcat img {
				width: 80px;
				height: 80px;
				margin-bottom: 1rem;
			}

			.single-fcat p {
				font-weight: 600;
				color: #2c3e50;
			}

			/* Animation for loading */
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

			.single-fcat, .job-listing {
				animation: fadeInUp 0.6s ease-out;
			}

			/* Share Modal Styles */
			.share-options {
				display: flex;
				flex-wrap: wrap;
				gap: 1rem;
				justify-content: center;
			}

			.share-btn {
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 0.75rem 1rem;
				border: none;
				border-radius: 8px;
				font-size: 0.9rem;
				font-weight: 500;
				cursor: pointer;
				transition: all 0.3s ease;
				min-width: 120px;
			}

			.share-btn i {
				margin-right: 0.5rem;
			}

			.share-btn.whatsapp {
				background: #25d366;
				color: #fff;
			}

			.share-btn.whatsapp:hover {
				background: #128c7e;
			}

			.share-btn.facebook {
				background: #1877f2;
				color: #fff;
			}

			.share-btn.facebook:hover {
				background: #166fe5;
			}

			.share-btn.twitter {
				background: #1da1f2;
				color: #fff;
			}

			.share-btn.twitter:hover {
				background: #1a91da;
			}

			.share-btn.linkedin {
				background: #0077b5;
				color: #fff;
			}

			.share-btn.linkedin:hover {
				background: #005885;
			}

			.share-btn.email {
				background: #ea4335;
				color: #fff;
			}

			.share-btn.email:hover {
				background: #d33b2c;
			}

			.share-btn.copy {
				background: #6c757d;
				color: #fff;
			}

			.share-btn.copy:hover {
				background: #5a6268;
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
								Jobs				
							</h1>	
							<p class="text-white link-nav"><a href="index.php">Home </a>  <span class="lnr lnr-arrow-right"></span>  <a href="career.php">Job List</a></p>
						</div>											
					</div>
				</div>
			</section>
			<!-- End banner Area -->	
			
			<!-- Start feature-cat Area -->
			<section class="feature-cat-area pt-100" id="category">
				<div class="container">
					<div class="row d-flex justify-content-center">
						<div class="menu-content pb-60 col-lg-10">
							<div class="title text-center">
								<h1 class="mb-10">Featured job categories, on-site and remote jobs</h1>
								<p>Discover diverse opportunities in top categories, tailored for both on-site and remote roles.</p>
							</div>
						</div>
					</div>						
					<div class="row">
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/marketing.png" alt="">
								</a>
								<p>Sales & Marketing</p>
							</div>
						</div>
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/tech-support.png" alt="">
								</a>
								<p>Engineering</p>
							</div>
						</div>
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/robotics.png" alt="">
								</a>
								<p>Construction</p>
							</div>
						</div>
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/education.png" alt="">
								</a>
								<p>Education & Training</p>
							</div>
						</div>
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/3d-anim.png" alt="">
								</a>
								<p>Arts, Design, & Media</p>
							</div>
						</div>
						<div class="col-lg-2 col-md-4 col-sm-6">
							<div class="single-fcat">
								<a href="#">
									<img src="img/web-dev.png" alt="">
								</a>
								<p>Information Technology</p>
							</div>			
						</div>																											
					</div>
				</div>	
			</section>
			<!-- End feature-cat Area -->
			
			<!-- Start post Area -->
			<section class="post-area section-gap" id="job-listings">
				<div class="container">
					<div class="row justify-content-center d-flex">
						<div id="job-postings-container" class="col-lg-10 post-list">
							<form method="GET" action="#job-listings">
								<label for="search"></label><br>
								<div class="input-container">
									<input type="text" id="search" name="search" placeholder="Search by: Company, Position, Department, Location" value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
									<button type="submit" class="search-btn"><i class="fa fa-search"></i></button>
								</div>
							</form>

							<div id="job-results">
								
							</div>

						</div>
						
					</div>
					<button id="load-more" onclick="loadMore()"><i class="fas fa-search-plus"></i> <a>Explore More Jobs</a></button>
				</div>
			</section>
			<!-- End post Area -->

					
			<!-- start footer Area -->		
			<footer class="footer-area section-gap">
				<div class="container">
					<div class="row d-flex justify-content-between">
						<div class="col-lg-3 col-md-12">
							<div class="single-footer-widget">
								<h6><span style="color: #fff;">Contact Information</h6>
								<p><strong><i class='bx bx-envelope'></i></strong> <span id="email-display">admin@investhoodit.co.za</span></p><br>
								<p><strong><i class='bx bx-phone'></i></strong> <span id="phone-display">068 246 0562</span></p><br>
								<p><strong><i class='bx bx-map'></i></strong> <span id="address-display">136 2nd St, Randjespark, Johannesburg, 1685</span></p><br>
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

			<!-- Share Modal -->
			<div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share this Job</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
						<div class="modal-body">
							<div class="share-options">
								<button class="share-btn whatsapp" onclick="shareToWhatsApp()"><i class="fab fa-whatsapp"></i> WhatsApp</button>
								<button class="share-btn facebook" onclick="shareToFacebook()"><i class="fab fa-facebook-f"></i> Facebook</button>
								<button class="share-btn twitter" onclick="shareToTwitter()"><i class="fab fa-twitter"></i> Twitter</button>
								<button class="share-btn linkedin" onclick="shareToLinkedIn()"><i class="fab fa-linkedin-in"></i> LinkedIn</button>
								<button class="share-btn email" onclick="shareViaEmail()"><i class="fas fa-envelope"></i> Email</button>
								<button class="share-btn copy" onclick="copyLink()"><i class="fas fa-copy"></i> Copy Link</button>
							</div>
						</div>
					</div>
				</div>
			</div>

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
				var currentPage = 0; //Keep track of the current page
				var searchQuery = ''; //Store the search query

				//Function to load job postings using AJAX
				function loadMore() {
					//Disable the button to prevent multiple clicks
					$('#load-more').prop('disabled', true);

					//Get the search query from the input field
					searchQuery = $('#search').val();

					//AJAX request to fetch job postings
					$.ajax({
						url: 'fetch_jobs.php',
						type: 'POST',
						data: {
							page: currentPage,
							search: searchQuery //Include search query in the request
						},
						success: function(response) {
							//Append the response to the job results container
							$('#job-results').append(response);

							//Increment the page number
							currentPage++;

							//Enable the button again
							$('#load-more').prop('disabled', false);

							//If no more job postings are found, hide the "Load More" button
							if (response === 'No job listings found.') {
								$('#load-more').hide();
							}
						}
					});
				}

				//Handle the search form submission
				$('#search-form').submit(function(e) {
					e.preventDefault(); //Prevent the form from submitting traditionally
					currentPage = 0; //Reset page number on new search
					$('#job-results').empty(); //Clear the previous results
					loadMore(); //Load the first batch of search results
				});

				//Initially load the first batch of job postings
				$(document).ready(function() {
					loadMore();
				});

				let shareUrl = '';
				let shareTitle = '';

				function openShareModal(url, title) {
					shareUrl = url;
					shareTitle = title;
					$('#shareModal').modal('show');
				}

				function shareToWhatsApp() {
					const text = encodeURIComponent(shareTitle + ' ' + shareUrl);
					window.open('https://wa.me/?text=' + text, '_blank');
				}

				function shareToFacebook() {
					const url = encodeURIComponent(shareUrl);
					window.open('https://www.facebook.com/sharer/sharer.php?u=' + url, '_blank');
				}

				function shareToTwitter() {
					const text = encodeURIComponent(shareTitle);
					const url = encodeURIComponent(shareUrl);
					window.open('https://twitter.com/intent/tweet?text=' + text + '&url=' + url, '_blank');
				}

				function shareToLinkedIn() {
					const url = encodeURIComponent(shareUrl);
					window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + url, '_blank');
				}

				function shareViaEmail() {
					const subject = encodeURIComponent(shareTitle);
					const body = encodeURIComponent('Check out this job: ' + shareUrl);
					window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
				}

				function copyLink() {
					navigator.clipboard.writeText(shareUrl).then(() => {
						alert('Link copied to clipboard!');
					}).catch(() => {
						prompt('Copy this link:', shareUrl);
					});
				}


			</script>
		</body>
	</html>



