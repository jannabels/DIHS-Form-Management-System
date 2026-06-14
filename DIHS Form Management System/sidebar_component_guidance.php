  <!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<!-- Sidebar Component -->
<aside class="sidebar">
  <!-- Sidebar header -->
  <header class="sidebar-header">
    <a href="#" class="header-logo">
      <img src="../images/dihslogo.png" alt="DIHS Logo">
    </a>
    <button class="toggler sidebar-toggler">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button class="toggler menu-toggler">
      <i class="fas fa-bars"></i>
    </button>
  </header>
  <nav class="sidebar-nav">
    <!-- Primary top nav guidance-->
    <ul class="nav-list primary-nav">
      <!-- <li class="nav-item">
        <a href="sf2.php" class="nav-link">
          <i class="nav-icon fas fa-calendar-alt"></i>
          <span class="nav-label">SF2 - Daily Attendance</span>
        </a>
        <span class="nav-tooltip">SF2 - Daily Attendance</span>
      </li> -->
      <!-- <li class="nav-item">
        <a href="../guidance/sf9.php" class="nav-link">
          <i class="nav-icon fas fa-file-alt"></i>
          <span class="nav-label">SF9 - Academic Record</span>
        </a>
        <span class="nav-tooltip">SF9 - Academic Record</span>
      </li> -->
      <li class="nav-item">
        <a href="sf10.php" class="nav-link">
          <i class="nav-icon fas fa-chart-bar"></i>
          <span class="nav-label">SF10 - Student Progress</span>
        </a>
        <span class="nav-tooltip">SF10 - Student Progress</span>
      </li>
    </ul>
    
    <!-- Secondary bottom nav -->
    <ul class="nav-list secondary-nav">
      <li class="nav-item">
        <a href="../guidance/guidanceindex.php" class="nav-link">
          <i class="nav-icon fas fa-users-cog"></i>
          <span class="nav-label">List of Enrollees</span>
        </a>
        <span class="nav-tooltip">List of Enrollees</span>
      </li>
      <li class="nav-item">
        <a href="../guidance/createclass.php" class="nav-link">
          <i class="nav-icon fas fa-user-plus"></i>
          <span class="nav-label">New Class Section</span>
        </a>
        <span class="nav-tooltip">Registrar</span>
      </li>
      <li class="nav-item">
        <a href="../guidance/assign_section.php" class="nav-link">
          <i class="nav-icon fas fa-user-plus"></i>
          <span class="nav-label">Student Sectioning</span>
        </a>
        <span class="nav-tooltip">Registrar</span>
      </li>
      <!-- <li class="nav-item">
        <a href="../principal/dashboard.php" class="nav-link">
          <i class="nav-icon fas fa-user-tie"></i>
          <span class="nav-label">Principal</span>
        </a>
        <span class="nav-tooltip">Principal</span>
      </li>
      <li class="nav-item">
        <a href="../ITpage/indexit.php" class="nav-link">
          <i class="nav-icon fas fa-cog"></i>
          <span class="nav-label">Admin</span>
        </a>
        <span class="nav-tooltip">Admin</span>
      </li> -->
      <li class="nav-item">
        <a href="../logout.php" class="nav-link" id="logoutBtn">
          <i class="nav-icon fas fa-sign-out-alt"></i>
          <span class="nav-label">Logout</span>
        </a>
        <span class="nav-tooltip">Logout</span>
      </li>
    </ul>
  </nav>
</aside>

<!-- Sidebar Styles -->
<style>
  /* Importing Google Fonts - Poppins */
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
  
  * {
    font-family: "Poppins", sans-serif;
  }
  
  body {
    min-height: 100vh;
    background: linear-gradient(#F0FFF4, #C6F6D5);
    margin: 0;
    padding: 0;
  }
  
  .sidebar {
    position: fixed;
    width: 270px;
    margin: 16px;
    border-radius: 16px;
    background: #16a34a;
    height: calc(100vh - 32px);
    transition: all 0.4s ease;
    z-index: 1000;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  }
  
  .sidebar.collapsed {
    width: 85px;
  }
  
  .sidebar .sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  .sidebar .header-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: white;
    font-weight: 600;
    font-size: 18px;
  }
  
  .sidebar .header-logo img {
    width: 32px;
    height: 32px;
    border-radius: 8px;
  }
  
  .sidebar .toggler {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }
  
  .sidebar .toggler:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  
  .sidebar .sidebar-nav {
    padding: 16px 0;
    height: calc(100% - 80px);
  }
  
  .sidebar .nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  
  .sidebar .nav-item {
    position: relative;
    margin: 4px 16px;
  }
  
  .sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
  }
  
  .sidebar .nav-link:hover,
  .sidebar .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
  }
  
  .sidebar .nav-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
  }
  
  .sidebar .nav-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .sidebar .nav-tooltip {
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: #1f2937;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1001;
    margin-left: 12px;
  }
  
  .sidebar .nav-tooltip::before {
    content: '';
    position: absolute;
    left: -4px;
    top: 50%;
    transform: translateY(-50%);
    border: 4px solid transparent;
    border-right-color: #1f2937;
  }
  
  .sidebar.collapsed .nav-label {
    display: none;
  }
  
  .sidebar.collapsed .nav-tooltip {
    opacity: 0;
    visibility: visible;
  }
  
  .sidebar .secondary-nav {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
  
  /* Main content styles */
  #main {
    transition: margin-left 0.4s ease;
  }
  
  /* Responsive Design */
  @media (min-width: 1025px) {
    #main {
      margin-left: 302px; /* 270px width + 32px margin (16px left + 16px right) */
    }
    
    body.sidebar-collapsed #main {
      margin-left: 117px; /* 85px collapsed + 32px margin */
    }
    
    .menu-toggler {
      display: none !important;
    }
  }
  
  @media (max-width: 1024px) {
    .sidebar {
      transform: translateX(-100%);
      margin: 0;
      border-radius: 0;
      height: 100vh;
      width: 270px;
    }
    
    .sidebar.mobile-open {
      transform: translateX(0);
    }
    
    #main {
      margin-left: 0 !important;
    }
    
    .sidebar-toggler {
      display: none !important;
    }
    
    .menu-toggler {
      display: block !important;
    }
  }
</style>

<!-- Sidebar JavaScript -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggler = document.querySelector('.sidebar-toggler');
    const menuToggler = document.querySelector('.menu-toggler');
    
    // Initial layout adjustment
    const adjustLayout = () => {
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove('collapsed');
        document.body.classList.remove('sidebar-collapsed');
      }
    };
    adjustLayout();
    window.addEventListener('resize', adjustLayout);
    
    // Toggle sidebar collapse (desktop)
    if (sidebarToggler) {
      sidebarToggler.addEventListener('click', function() {
        if (window.innerWidth > 1024) {
          sidebar.classList.toggle('collapsed');
          const collapsed = sidebar.classList.contains('collapsed');
          const event = new CustomEvent('sidebarToggle', { detail: { collapsed } });
          window.dispatchEvent(event);
        }
      });
    }
    
    // Mobile menu toggle
    if (menuToggler) {
      menuToggler.addEventListener('click', function() {
        sidebar.classList.toggle('mobile-open');
        const active = sidebar.classList.contains('mobile-open');
        const event = new CustomEvent('mobileMenuToggle', { detail: { active } });
        window.dispatchEvent(event);
      });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 1024) {
        if (!sidebar.contains(e.target) && !menuToggler.contains(e.target)) {
          sidebar.classList.remove('mobile-open');
          const event = new CustomEvent('mobileMenuToggle', { detail: { active: false } });
          window.dispatchEvent(event);
        }
      }
    });
    
    // Set active nav item based on current page
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
      if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
        link.classList.add('active');
      }
    });
  });
</script> 