<?php
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5.3 CSS (Offline) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- FontAwesome (Offline) -->
    <link rel="stylesheet" href="assets/css/all.min.css">

    <style>
        :root {
            --primary-color:
                <?php echo PRIMARY_COLOR; ?>
            ;
            --secondary-color:
                <?php echo SECONDARY_COLOR; ?>
            ;
            --header-font:
                <?php echo HEADER_FONT; ?>
            ;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }

        /* Hospital Professional Header */
        .hospital-header {
            background: white;
            border-bottom: 4px solid var(--primary-color);
            padding: 15px 0;
            margin-bottom: 20px;
        }

        .hospital-logo {
            max-height: 80px;
        }

        .hospital-name {
            font-family: var(--header-font);
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .hospital-address {
            font-size: 14px;
            color: #555;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -15rem;
            transition: margin .25s ease-out;
            background-color: var(--secondary-color);
            color: white;
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
            background-color: rgba(0, 0, 0, 0.2);
        }

        #sidebar-wrapper .list-group {
            width: 15rem;
        }

        #sidebar-wrapper .list-group-item {
            background-color: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: none;
            padding: 12px 20px;
        }

        #sidebar-wrapper .list-group-item:hover,
        #sidebar-wrapper .list-group-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        #page-content-wrapper {
            min-width: 100vw;
        }

        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }

        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }

            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }

            #wrapper.toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
        }

        /* Print Styling */
        @media print {

            .no-print,
            #sidebar-wrapper,
            .navbar {
                display: none !important;
            }

            #page-content-wrapper {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .hospital-header {
                border-bottom: 2px solid black;
            }
        }
    </style>
</head>

<body>

    <div class="d-flex" id="wrapper">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php include_once 'sidebar.php'; ?>
            <?php
        endif; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-3 no-print">
                    <button class="btn btn-primary" id="menu-toggle"><i class="fas fa-bars"></i></button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name'] ?? 'User'; ?>
                                    (<?php echo $_SESSION['role'] ?? ''; ?>)
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <?php
            endif; ?>

            <div class="container-fluid p-0">
                <!-- Universal Hospital Header (Visible on Prints and Top of pages) -->
                <div class="hospital-header text-center">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center text-md-end">
                            <img src="<?php echo APP_LOGO; ?>" alt="Logo" class="hospital-logo">
                        </div>
                        <div class="col-md-8 text-center text-md-start">
                            <a class="navbar-brand me-0" href="index.php">
                                <img src="<?php echo APP_LOGO; ?>" alt="Logo" height="40"
                                    class="d-inline-block align-text-top me-2">
                                <?php echo APP_SHORT_NAME; ?>
                            </a>
                            <div class="hospital-address">
                                <i class="fas fa-map-marker-alt"></i> <?php echo APP_ADDRESS; ?><br>
                                <i class="fas fa-phone"></i> <?php echo APP_PHONE; ?> | <i class="fas fa-envelope"></i>
                                <?php echo APP_EMAIL; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <!-- Page Content Starts Here -->