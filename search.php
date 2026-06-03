<?php
include('config.php');

// Get search parameters
$location = isset($_GET['location']) ? $_GET['location'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Build the query to include both internal and external jobs
$query = "(SELECT job_postings.job_id, job_postings.position, job_postings.location, job_postings.job_description, job_postings.salary, job_postings.date_posted, companies.company_name, departments.department_name, 'internal' AS type, '' AS url, '' AS source, '' AS apply_link
          FROM job_postings
          LEFT JOIN companies ON job_postings.company_id = companies.company_id
          LEFT JOIN departments ON job_postings.department_id = departments.department_id
          WHERE 1";

$internal_conditions = [];
if (!empty($location)) {
    $internal_conditions[] = "job_postings.location LIKE '%$location%'";
}
if (!empty($department)) {
    $internal_conditions[] = "job_postings.position LIKE '%$department%'";
}

if (!empty($internal_conditions)) {
    $query .= " AND " . implode(" AND ", $internal_conditions);
}

$query .= ") UNION ALL (SELECT external_job_id AS job_id, title AS position, location, description AS job_description, salary, date_fetched AS date_posted, company AS company_name, '' AS department_name, 'external' AS type, url, '' AS source, url AS apply_link
          FROM external_jobs
          WHERE 1";

$external_conditions = [];
if (!empty($location)) {
    $external_conditions[] = "location LIKE '%$location%'";
}
if (!empty($department)) {
    $external_conditions[] = "title LIKE '%$department%'";
}

if (!empty($external_conditions)) {
    $query .= " AND " . implode(" AND ", $external_conditions);
}

$query .= ") ORDER BY date_posted DESC";

$result = $conn->query($query);
?>

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
    <title>Search Results - Investhood IT Recruitment</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,400,300,500,600,700" rel="stylesheet">
    <!--
    CSS
    ============================================== -->
    <link rel="stylesheet" href="css/linearicons.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/main.css">

    <style>
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

        /* Header Styling */
        header {
            background: transparent;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
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
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .banner-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
        }

        .overlay-bg {
            transition: opacity 2s ease-in-out;
        }

        .banner-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 2s ease-out;
        }

        /* Job Listings */
        .post-area {
            padding: 3rem 0;
        }

        #job-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
        }

        .job-listing {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
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

        .job-listing button {
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

        .job-listing button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
            background: #34495e;
            color: #fff;
        }

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

        /* Responsive Design */
        @media (max-width: 768px) {
            .banner-content h1 {
                font-size: 2rem;
            }

            #job-results {
                grid-template-columns: 1fr;
                padding: 0;
            }

            .job-listing {
                padding: 1.5rem;
            }
        }

        /* Animations */
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

        .job-listing {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Icon Colors */
        .job-listing .fas.fa-building {
            color: #3498db; /* Blue for company */
        }

        .job-listing .fas.fa-briefcase {
            color: #e74c3c; /* Red for position */
        }

        .job-listing .fas.fa-map-marker-alt {
            color: #27ae60; /* Green for location */
        }

        .job-listing .fas.fa-dollar-sign {
            color: #f39c12; /* Orange for salary */
        }

        .job-listing .fas.fa-file-alt {
            color: #9b59b6; /* Purple for description */
        }

        .job-listing button .fas.fa-eye {
            color: #fff; /* White for view details */
        }

        .job-listing button .fas.fa-paper-plane {
            color: #fff; /* White for apply now */
        }

        .no-results .fas.fa-search {
            color: #e74c3c; /* Red for no results */
        }

        /* Explore More Button */
        .explore-more {
            text-align: center;
            margin-top: 2rem;
        }

        .explore-more button {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 400;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .explore-more button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            background: #34495e;
        }

        .explore-more button i {
            margin-right: 0.5rem;
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
                <nav id="nav-menu-container">
                    <ul class="nav-menu">
                        <li class="menu-active"><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="jobs.php">Jobs</a></li>
                        <li><a href="contact.php">Contact</a></li>
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
                <div class="banner-content col-lg-12">
                    <h1 class="text-white">
                        Search Results for Job Postings
                    </h1>
                    <p class="text-white">
                        <?php
                        if (!empty($location) || !empty($department)) {
                            echo "Searching for: ";
                            if (!empty($location)) echo "Location - $location";
                            if (!empty($department)) echo " Position - $department";
                        } else {
                            echo "No search criteria provided. Showing all jobs.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- End banner Area -->

    <!-- Start post Area -->
    <section class="post-area section-gap" id="job-listings">
        <div class="container">
            <div class="row justify-content-center d-flex">
                <div id="job-postings-container" class="col-lg-10 post-list">
                    <div id="job-results">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $is_external = $row['type'] === 'external';
                                $apply_url = $is_external ? $row['apply_link'] : 'apply.php?job_id=' . $row['job_id'] . '&position=' . urlencode($row['position']);
                                $view_url = $is_external ? $row['apply_link'] : 'job_details.php?job_id=' . $row['job_id'];

                                echo '<div class="job-listing">';
                                echo '<div class="title"><i class="fas fa-briefcase"></i> ' . htmlspecialchars($row['position']) . '</div>';
                                echo '<p><i class="fas fa-building"></i> ' . htmlspecialchars($row['company_name']) . '</p>';
                                echo '<p><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</p>';
                                $description = $row['job_description'];
                                $clean_description = str_replace(["\n", "\r", "\\n", "/n"], ' ', $description); // Remove newlines and literal newline strings
                                $clean_description = htmlspecialchars($clean_description);
                                $truncated_description = strlen($clean_description) > 300 ? substr($clean_description, 0, 300) . '...' : $clean_description;
                                echo "<p><strong></strong> " . $truncated_description . "</p>";
                                if ($is_external) {
                                    echo '<p><i class="fas fa-calendar"></i> Posted on: ' . date('F j, Y', strtotime($row['date_posted'])) . '</p>';
                                }
                                if ($is_external) {
                                    echo '<button onclick="shareJob(\'' . $row['job_id'] . '\', \'' . htmlspecialchars($row['position']) . '\', \'' . htmlspecialchars($row['url']) . '\')"><i class="fas fa-share"></i> Share</button>';
                                    echo '<button onclick="window.open(\'' . $view_url . '\', \'_blank\')"><i class="fas fa-eye"></i> View Post</button>';
                                    echo '<button onclick="window.open(\'' . $apply_url . '\', \'_blank\')"><i class="fas fa-paper-plane"></i> Apply Externally</button>';
                                } else {
                                    echo '<button onclick="shareJob(\'' . $row['job_id'] . '\', \'' . htmlspecialchars($row['position']) . '\')"><i class="fas fa-share"></i> Share</button>';
                                    echo '<button onclick="window.location.href=\'' . $view_url . '\'"><i class="fas fa-eye"></i> View Details</button>';
                                    echo '<button onclick="window.location.href=\'' . $apply_url . '\'"><i class="fas fa-paper-plane"></i> Apply Now</button>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="no-results"><i class="fas fa-search"></i> No job listings found matching your search criteria.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
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

        function shareJob(jobId, position, url) {
            const finalUrl = url || (window.location.origin + '/job_details.php?job_id=' + jobId);
            const title = 'Check out this job: ' + position;
            openShareModal(finalUrl, title);
        }
    </script>
</body>
</html>
