<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ url('/') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ url('/') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ asset('assets/images/logo-light.png') }}" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Menu</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                        <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Dashboards</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/analytics') }}" class="nav-link" data-key="t-analytics"> Analytics </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/crm') }}" class="nav-link" data-key="t-crm"> CRM </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/') }}" class="nav-link" data-key="t-ecommerce"> Ecommerce </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/crypto') }}" class="nav-link" data-key="t-crypto"> Crypto </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/projects') }}" class="nav-link" data-key="t-projects"> Projects </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/nft') }}" class="nav-link" data-key="t-nft"> NFT</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/dashboard/job') }}" class="nav-link" data-key="t-job">Job</a>
                            </li>
                        </ul>
                    </div>
                </li> <!-- end Dashboard Menu -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarApps" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApps">
                        <i class="ri-apps-2-line"></i> <span data-key="t-apps">Apps</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarApps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="#sidebarCalendar" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCalendar" data-key="t-calender">
                                    Calendar
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarCalendar">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/calendar') }}" class="nav-link" data-key="t-main-calender"> Main Calender </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/calendar/month-grid') }}" class="nav-link" data-key="t-month-grid"> Month Grid </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/apps/chat') }}" class="nav-link" data-key="t-chat"> Chat </a>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarEmail" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarEmail" data-key="t-email">
                                    Email
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarEmail">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/mailbox') }}" class="nav-link" data-key="t-mailbox"> Mailbox </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#sidebaremailTemplates" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebaremailTemplates" data-key="t-email-templates">
                                                Email Templates
                                            </a>
                                            <div class="collapse menu-dropdown" id="sidebaremailTemplates">
                                                <ul class="nav nav-sm flex-column">
                                                    <li class="nav-item">
                                                        <a href="{{ url('/apps/email/basic') }}" class="nav-link" data-key="t-basic-action"> Basic Action </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="{{ url('/apps/email/ecommerce') }}" class="nav-link" data-key="t-ecommerce-action"> Ecommerce Action </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarEcommerce" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarEcommerce" data-key="t-ecommerce">
                                    Ecommerce
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarEcommerce">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/products') }}" class="nav-link" data-key="t-products"> Products </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/product-details') }}" class="nav-link" data-key="t-product-Details"> Product Details </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/add-product') }}" class="nav-link" data-key="t-create-product"> Create Product </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/orders') }}" class="nav-link" data-key="t-orders">
                                                Orders </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/order-details') }}" class="nav-link" data-key="t-order-details"> Order Details </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/customers') }}" class="nav-link" data-key="t-customers"> Customers </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/cart') }}" class="nav-link" data-key="t-shopping-cart"> Shopping Cart </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/checkout') }}" class="nav-link" data-key="t-checkout"> Checkout </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/sellers') }}" class="nav-link" data-key="t-sellers">
                                                Sellers </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/ecommerce/seller-details') }}" class="nav-link" data-key="t-sellers-details"> Seller Details </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarProjects" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarProjects" data-key="t-projects">
                                    Projects
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarProjects">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/projects/list') }}" class="nav-link" data-key="t-list"> List
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/projects/overview') }}" class="nav-link" data-key="t-overview"> Overview </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/projects/create') }}" class="nav-link" data-key="t-create-project"> Create Project </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarTasks" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTasks" data-key="t-tasks"> Tasks
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarTasks">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/tasks/kanban') }}" class="nav-link" data-key="t-kanbanboard">
                                                Kanban Board </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/tasks/list-view') }}" class="nav-link" data-key="t-list-view">
                                                List View </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/tasks/details') }}" class="nav-link" data-key="t-task-details"> Task Details </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarCRM" class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCRM" data-key="t-crm"> CRM
                                </a>
                                <div class="collapse menu-dropdown" id="sidebarCRM">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/crm/contacts') }}" class="nav-link" data-key="t-contacts">
                                                Contacts </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/crm/companies') }}" class="nav-link" data-key="t-companies">
                                                Companies </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/crm/deals') }}" class="nav-link" data-key="t-deals"> Deals
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ url('/apps/crm/leads') }}" class="nav-link" data-key="t-leads"> Leads
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/apps/file-manager') }}" class="nav-link"> <span data-key="t-file-manager">File Manager</span></a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/apps/todo') }}" class="nav-link"> <span data-key="t-to-do">To Do</span></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarLayouts" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                        <i class="ri-layout-3-line"></i> <span data-key="t-layouts">Layouts</span> <span class="badge badge-pill bg-danger" data-key="t-hot">Hot</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarLayouts">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/layouts/horizontal') }}" target="_blank" class="nav-link" data-key="t-horizontal">Horizontal</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/layouts/detached') }}" target="_blank" class="nav-link" data-key="t-detached">Detached</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/layouts/two-column') }}" target="_blank" class="nav-link" data-key="t-two-column">Two Column</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/layouts/vertical-hovered') }}" target="_blank" class="nav-link" data-key="t-hovered">Hovered</a>
                            </li>
                        </ul>
                    </div>
                </li> <!-- end Dashboard Menu -->

                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Pages</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAuth" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAuth">
                        <i class="ri-account-circle-line"></i> <span data-key="t-authentication">Authentication</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAuth">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/auth/signin') }}" class="nav-link" data-key="t-signin"> Sign In
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/auth/signup') }}" class="nav-link" data-key="t-signup"> Sign Up
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/auth/password/reset') }}" class="nav-link" data-key="t-password-reset">
                                    Password Reset
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/auth/password/change') }}" class="nav-link" data-key="t-password-create">
                                    Password Create
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/auth/lock-screen') }}" class="nav-link" data-key="t-lock-screen">
                                    Lock Screen
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/auth/logout') }}" class="nav-link" data-key="t-logout"> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPages" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPages">
                        <i class="ri-pages-line"></i> <span data-key="t-pages">Pages</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPages">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/pages/starter') }}" class="nav-link" data-key="t-starter"> Starter </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/profile') }}" class="nav-link" data-key="t-profile"> Profile </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/team') }}" class="nav-link" data-key="t-team"> Team </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/timeline') }}" class="nav-link" data-key="t-timeline"> Timeline </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/faqs') }}" class="nav-link" data-key="t-faqs"> FAQs </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/pricing') }}" class="nav-link" data-key="t-pricing"> Pricing </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/pages/gallery') }}" class="nav-link" data-key="t-gallery"> Gallery </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-components">Components</span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUI" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUI">
                        <i class="ri-pencil-ruler-2-line"></i> <span data-key="t-base-ui">Base UI</span>
                    </a>
                    <div class="collapse menu-dropdown mega-dropdown-menu" id="sidebarUI">
                        <div class="row">
                            <div class="col-lg-4">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/alerts') }}" class="nav-link" data-key="t-alerts">Alerts</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/badges') }}" class="nav-link" data-key="t-badges">Badges</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/buttons') }}" class="nav-link" data-key="t-buttons">Buttons</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/colors') }}" class="nav-link" data-key="t-colors">Colors</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/cards') }}" class="nav-link" data-key="t-cards">Cards</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-4">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/dropdowns') }}" class="nav-link" data-key="t-dropdowns">Dropdowns</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/modals') }}" class="nav-link" data-key="t-modals">Modals</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/progress') }}" class="nav-link" data-key="t-progress">Progress</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/notifications') }}" class="nav-link" data-key="t-notifications">Notifications</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-lg-4">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/typography') }}" class="nav-link" data-key="t-typography">Typography</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ url('/ui/utilities') }}" class="nav-link" data-key="t-utilities">Utilities</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ url('/widgets') }}">
                        <i class="ri-honour-line"></i> <span data-key="t-widgets">Widgets</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarForms" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarForms">
                        <i class="ri-file-list-3-line"></i> <span data-key="t-forms">Forms</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarForms">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/forms/elements') }}" class="nav-link" data-key="t-basic-elements">Basic Elements</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/forms/validation') }}" class="nav-link" data-key="t-validation">Validation</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/forms/wizard') }}" class="nav-link" data-key="t-wizard">Wizard</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarTables" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarTables">
                        <i class="ri-layout-grid-line"></i> <span data-key="t-tables">Tables</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarTables">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/tables/basic') }}" class="nav-link" data-key="t-basic-tables">Basic Tables</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/tables/datatables') }}" class="nav-link" data-key="t-datatables">Datatables</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarCharts" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCharts">
                        <i class="ri-pie-chart-line"></i> <span data-key="t-charts">Charts</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarCharts">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/charts/apex') }}" class="nav-link" data-key="t-apexcharts">Apexcharts</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/charts/chartjs') }}" class="nav-link" data-key="t-chartjs">Chartjs</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarIcons" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarIcons">
                        <i class="ri-compasses-2-line"></i> <span data-key="t-icons">Icons</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarIcons">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/icons/remix') }}" class="nav-link" data-key="t-remix">Remix</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/icons/boxicons') }}" class="nav-link" data-key="t-boxicons">Boxicons</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/icons/material') }}" class="nav-link" data-key="t-material-design">Material Design</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarMaps" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMaps">
                        <i class="ri-map-pin-line"></i> <span data-key="t-maps">Maps</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarMaps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('/maps/google') }}" class="nav-link" data-key="t-google">Google</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/maps/vector') }}" class="nav-link" data-key="t-vector">Vector</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('/maps/leaflet') }}" class="nav-link" data-key="t-leaflet">Leaflet</a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
