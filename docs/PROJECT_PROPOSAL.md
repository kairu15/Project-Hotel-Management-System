# Project Proposal: Bayawan Bai Hotel Management System

---

## 1. Executive Summary

**Project Title:** Bayawan Bai Hotel Management System  
**Prepared For:** Bayawan Bai Hotel  
**Prepared By:** Development Team  
**Date:** April 2026  
**Version:** 1.0  

This proposal outlines the development of a comprehensive, full-featured Hotel Management System designed specifically for Bayawan Bai Hotel. The system will modernize hotel operations, streamline booking processes, enhance guest experiences, and provide powerful administrative tools for efficient business management.

---

## 2. Project Background and Rationale

### 2.1 Current Situation
Bayawan Bai Hotel currently operates with manual or disconnected systems for managing:
- Guest reservations and bookings
- Room inventory and availability
- Payment processing
- Staff scheduling and operations
- Event space management
- Food and beverage services
- Maintenance tracking

### 2.2 Problem Statement
The absence of an integrated management system results in:
- **Inefficient Operations:** Manual booking processes lead to overbookings and scheduling conflicts
- **Poor Guest Experience:** Lack of online booking capabilities limits customer convenience
- **Data Fragmentation:** Information silos prevent comprehensive business analytics
- **Revenue Loss:** Missed opportunities for upselling and dynamic pricing
- **Operational Delays:** Manual check-in/check-out processes slow down front desk operations

### 2.3 Proposed Solution
Develop an integrated Hotel Management System that combines:
- Customer-facing booking portal
- Staff operations dashboard
- Administrative control center
- Real-time inventory management
- Payment processing integration
- Comprehensive reporting and analytics

---

## 3. Project Objectives

### 3.1 Primary Objectives
1. **Automate Booking Operations** - Enable 24/7 online room and event reservations
2. **Streamline Check-In/Check-Out** - Reduce guest wait times through digital processes
3. **Centralize Data Management** - Create a single source of truth for all hotel operations
4. **Enhance Guest Experience** - Provide intuitive interfaces for booking and service requests
5. **Improve Revenue Management** - Enable dynamic pricing and promotional campaigns

### 3.2 Secondary Objectives
1. **Staff Efficiency** - Reduce manual administrative workload by 60%
2. **Inventory Control** - Real-time tracking of rooms, amenities, and food inventory
3. **Analytics-Driven Decisions** - Generate actionable business intelligence reports
4. **Multi-Platform Accessibility** - Responsive design for desktop, tablet, and mobile devices
5. **Integration Capabilities** - Support for payment gateways and third-party services

---

## 4. System Description and Scope

### 4.1 System Overview
The Bayawan Bai Hotel Management System is a dynamic, database-driven web application featuring three distinct user portals:

1. **Guest Portal** - Customer-facing website for browsing, booking, and managing reservations
2. **Staff Portal** - Operational interface for receptionists and hotel staff
3. **Admin Portal** - Management dashboard for administrators and hotel managers

### 4.2 Functional Scope

#### 4.2.1 Guest-Facing Features
| Module | Features |
|--------|----------|
| **Homepage** | Hero slider, featured rooms, promotions, hotel highlights |
| **Room Management** | Room listings with filtering, detailed room pages, image galleries |
| **Booking Engine** | Real-time availability checking, dynamic pricing, reservation form |
| **User Accounts** | Registration, login, profile management, password recovery |
| **My Bookings** | Booking history, details view, cancellation requests |
| **Dining Services** | Menu browsing, online food ordering, room service requests |
| **Event Spaces** | Venue browsing, event booking inquiries, virtual tours |
| **Amenities** | Spa, pool, gym services with pricing and booking |
| **Gallery** | Photo gallery with lightbox viewing |
| **Information Pages** | About, contact, location, FAQ, privacy policy, terms |

#### 4.2.2 Staff Portal Features
| Module | Features |
|--------|----------|
| **Dashboard** | Daily operations overview, check-in/out status, pending tasks |
| **Check-In** | Guest arrival processing, ID verification, key assignment |
| **Check-Out** | Departure processing, billing review, feedback collection |
| **Booking Management** | View all bookings, confirm reservations, modify details |
| **Walk-In Bookings** | Direct reservation creation for on-site guests |
| **Event Bookings** | Event reservation management, space allocation |
| **Food Orders** | Process room service orders, track delivery status |
| **QR Code Scanner** | Digital check-in/out verification, food order validation |
| **Inventory** | Stock level monitoring, item tracking, reorder alerts |
| **Maintenance** | Maintenance request logging, task assignment, status tracking |
| **Calendar View** | Visual booking calendar for rooms and event spaces |

#### 4.2.3 Admin Portal Features
| Module | Features |
|--------|----------|
| **Admin Dashboard** | Key performance indicators, revenue metrics, occupancy rates |
| **User Management** | Customer accounts, staff accounts, role assignments |
| **Room Management** | Room inventory, categories, pricing, maintenance status |
| **Booking Oversight** | All booking management, conflict resolution, bulk operations |
| **Payment Management** | Transaction history, refund processing, payment gateway config |
| **Analytics** | Business intelligence dashboards, trend analysis |
| **Reports** | Custom report generation, export to PDF/Excel |
| **Content Management** | Homepage slider, gallery, promotions, FAQs |
| **Reviews & Ratings** | Customer feedback management, response system |
| **Event Management** | Event space configuration, booking rules, pricing tiers |
| **Food Services** | Menu categories, menu items, food inventory, pricing |
| **Inventory System** | Category management, item tracking, supplier information |
| **Staff Management** | Permission roles, work schedules, performance tracking |
| **System Settings** | Hotel configuration, notification settings, system parameters |
| **Virtual Tours** | 360° tour management, hotspot configuration |

### 4.3 Technical Scope

#### Technology Stack
| Component | Technology |
|-----------|------------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ / MariaDB 10.2+ |
| **Frontend** | HTML5, Inline CSS, Vanilla JavaScript (ES6+) |
| **Icons** | Font Awesome 6.4.0 |
| **Fonts** | Google Fonts (Playfair Display, Lato) |
| **Email** | PHPMailer 7.0+ |
| **Package Manager** | Composer 2.0+ |

#### Integration Capabilities
- Payment gateway integration (PayPal, Stripe, etc.)
- Email notification services
- QR code generation and scanning
- Social login (Google, Facebook)
- Multi-language support framework
- Google Maps integration

---

## 5. System Architecture

### 5.1 Database Architecture
The system utilizes a relational database with the following key entities:

- **Users** (guests, staff, administrators)
- **Rooms** (inventory, categories, pricing)
- **Bookings** (reservations, status tracking)
- **Payments** (transactions, methods, refunds)
- **Events** (spaces, bookings, configurations)
- **Food Services** (menu categories, items, orders)
- **Inventory** (categories, items, stock levels)
- **Maintenance** (requests, assignments, tracking)

### 5.2 Security Architecture
- Password hashing with bcrypt
- Session management with secure cookies
- CSRF protection
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- Role-based access control (RBAC)

### 5.3 File Structure
```
bayawanhotel/
├── Root Pages (index.php, rooms.php, booking.php, etc.)
├── auth/ (Authentication module)
├── user/ (User dashboard module)
├── staff/ (Staff operations module)
├── admin/ (Administration module)
├── includes/ (Shared components, functions, database connection)
├── api/ (API endpoints for AJAX requests)
├── assets/ (Images, uploads, static resources)
├── database/ (Schema and migration files)
├── cache/ (Temporary cache files)
├── logs/ (Application logs)
└── vendor/ (Third-party dependencies)
```

---

## 6. Project Deliverables

### 6.1 Core Deliverables
| Deliverable | Description |
|-------------|-------------|
| **Source Code** | Complete PHP application codebase with inline documentation |
| **Database Schema** | SQL files for database creation and initial data |
| **User Manual** | Comprehensive guide for end-users |
| **Admin Manual** | Documentation for administrators |
| **Staff Manual** | Training guide for hotel staff |
| **Technical Documentation** | System architecture, API documentation |
| **Deployment Guide** | Installation and configuration instructions |

### 6.2 Module-Specific Deliverables
1. **Guest Portal** - Fully functional public-facing website
2. **User Dashboard** - Account management and booking history
3. **Staff Portal** - Complete operational interface
4. **Admin Dashboard** - Full administrative control panel
5. **Payment Integration** - Working payment processing system
6. **Notification System** - Email and in-app notifications
7. **QR Code System** - Generation and scanning capabilities
8. **Virtual Tour Module** - 360° tour integration

---

## 7. Project Timeline

### 7.1 Development Phases

| Phase | Duration | Key Activities |
|-------|----------|----------------|
| **Phase 1: Planning & Design** | 2 weeks | Requirements finalization, UI/UX design, database design |
| **Phase 2: Core Development** | 6 weeks | Authentication, database layer, core modules |
| **Phase 3: Feature Development** | 8 weeks | Guest portal, staff portal, admin portal |
| **Phase 4: Integration** | 3 weeks | Payment gateways, email services, third-party APIs |
| **Phase 5: Testing** | 3 weeks | Unit testing, integration testing, user acceptance testing |
| **Phase 6: Deployment** | 2 weeks | Production setup, data migration, go-live |
| **Phase 7: Training & Handover** | 2 weeks | Staff training, documentation handover, warranty period |

### 7.2 Milestone Schedule

| Milestone | Target Date | Deliverables |
|-----------|-------------|--------------|
| M1: Project Kickoff | Week 1 | Signed proposal, project charter |
| M2: Design Approval | Week 2 | UI mockups, database schema |
| M3: Core Platform | Week 8 | Authentication, basic booking flow |
| M4: Guest Portal Complete | Week 12 | Public website, user accounts |
| M5: Staff Portal Complete | Week 14 | Check-in/out, booking management |
| M6: Admin Portal Complete | Week 16 | Full admin functionality |
| M7: Integration Complete | Week 19 | Payments, notifications, QR codes |
| M8: Testing Complete | Week 22 | Test reports, bug fixes |
| M9: Go-Live | Week 24 | Production deployment |
| M10: Project Closure | Week 26 | Training complete, handover signed |

---

## 8. Resource Requirements

### 8.1 Development Team
| Role | Count | Responsibilities |
|------|-------|------------------|
| **Project Manager** | 1 | Coordination, client communication, timeline management |
| **Lead Developer** | 1 | Architecture, code review, technical decisions |
| **Backend Developers** | 2 | PHP development, database design, API development |
| **Frontend Developers** | 2 | HTML/CSS/JavaScript, responsive design, UI implementation |
| **QA Engineer** | 1 | Testing, bug reporting, quality assurance |
| **Technical Writer** | 1 | Documentation, user manuals, guides |

### 8.2 Infrastructure Requirements
| Resource | Specification |
|----------|---------------|
| **Web Server** | Apache/Nginx with PHP 7.4+ support |
| **Database Server** | MySQL 5.7+ or MariaDB 10.2+ |
| **SSL Certificate** | Valid SSL for secure HTTPS connections |
| **Hosting Environment** | Linux-based server with cPanel/Plesk access |
| **Backup Storage** | Automated daily backups with 30-day retention |
| **Email Server** | SMTP server for transactional emails |

### 8.3 Software Requirements
- XAMPP/WAMP/MAMP for local development
- Git for version control
- Composer for PHP dependency management
- MySQL Workbench for database design
- VS Code or PHPStorm as IDE
- Browser DevTools for frontend debugging

---

## 9. Budget Estimate

### 9.1 Development Costs
| Item | Hours | Rate | Amount |
|------|-------|------|--------|
| Project Management | 80 hrs | $50/hr | $4,000 |
| Lead Development | 200 hrs | $60/hr | $12,000 |
| Backend Development | 400 hrs | $45/hr | $18,000 |
| Frontend Development | 350 hrs | $40/hr | $14,000 |
| Quality Assurance | 120 hrs | $35/hr | $4,200 |
| Technical Documentation | 60 hrs | $40/hr | $2,400 |
| **Subtotal** | | | **$54,600** |

### 9.2 Infrastructure Costs (Annual)
| Item | Cost |
|------|------|
| Web Hosting (VPS) | $1,200/year |
| Domain Registration | $20/year |
| SSL Certificate | $80/year |
| Backup Services | $300/year |
| Email Services | $240/year |
| **Subtotal** | **$1,840/year** |

### 9.3 Third-Party Services (Annual)
| Item | Cost |
|------|------|
| Payment Gateway Fees | Transaction-based |
| SMS Gateway (optional) | $200/year |
| Google Maps API | $200/year |
| **Subtotal** | **$400/year** |

### 9.4 Total Project Investment
| Category | Amount |
|----------|--------|
| Development | $54,600 |
| Infrastructure (Year 1) | $1,840 |
| Third-Party Services (Year 1) | $400 |
| Contingency (10%) | $5,684 |
| **TOTAL** | **$62,524** |

---

## 10. Risk Management

### 10.1 Identified Risks

| Risk | Probability | Impact | Mitigation Strategy |
|------|-------------|--------|---------------------|
| **Scope Creep** | High | Medium | Strict change control process, clear requirements documentation |
| **Technical Complexity** | Medium | Medium | Experienced development team, proof-of-concept for complex features |
| **Integration Failures** | Medium | High | Early testing of payment and email integrations, fallback options |
| **Data Migration Issues** | Low | High | Comprehensive backup strategy, phased migration approach |
| **User Adoption** | Medium | Medium | User-friendly design, comprehensive training, user manuals |
| **Performance Issues** | Low | High | Load testing, optimization, caching implementation |

### 10.2 Risk Mitigation Plan
1. **Weekly Progress Reviews** - Early identification of issues
2. **Prototype Development** - Validate complex features early
3. **Staged Rollout** - Pilot testing with limited users
4. **Comprehensive Testing** - Unit, integration, and UAT phases
5. **Documentation** - Detailed technical and user documentation
6. **Training Program** - Hands-on training for all user types

---

## 11. Quality Assurance

### 11.1 Testing Strategy
| Test Type | Scope | Tools |
|-----------|-------|-------|
| **Unit Testing** | Individual functions and modules | PHPUnit |
| **Integration Testing** | Module interactions and APIs | Postman, Selenium |
| **System Testing** | End-to-end workflows | Manual + Automated |
| **UAT** | Real-world scenarios with stakeholders | Manual testing |
| **Performance Testing** | Load and stress testing | Apache JMeter |
| **Security Testing** | Vulnerability scanning | OWASP ZAP |

### 11.2 Quality Metrics
- **Code Coverage:** Minimum 70% test coverage
- **Bug Severity:** No critical bugs at launch
- **Performance:** Page load under 3 seconds
- **Uptime:** 99.5% availability target
- **User Satisfaction:** Minimum 4.0/5.0 rating

---

## 12. Post-Implementation Support

### 12.1 Warranty Period
- **Duration:** 3 months post-deployment
- **Coverage:** Bug fixes, minor adjustments
- **Response Time:** 24-48 hours for critical issues

### 12.2 Maintenance Options
| Plan | Includes | Monthly Cost |
|------|----------|--------------|
| **Basic** | Bug fixes, security updates | $500 |
| **Standard** | Basic + feature enhancements | $1,000 |
| **Premium** | Standard + priority support, monthly reports | $1,500 |

### 12.3 Future Enhancements (Roadmap)
- Mobile application (iOS/Android)
- AI-powered chatbot for guest inquiries
- Revenue management system with dynamic pricing
- Channel manager for OTA integrations (Booking.com, Agoda)
- Guest loyalty program module
- Housekeeping mobile app
- Advanced analytics with predictive modeling

---

## 13. Acceptance Criteria

### 13.1 Functional Criteria
- [ ] All guest portal features operational
- [ ] All staff portal features operational
- [ ] All admin portal features operational
- [ ] Payment processing working correctly
- [ ] Email notifications functioning
- [ ] QR code generation and scanning functional
- [ ] Virtual tour integration complete
- [ ] Multi-user concurrent access supported

### 13.2 Non-Functional Criteria
- [ ] System response time under 3 seconds
- [ ] 99.5% uptime during business hours
- [ ] Compatible with modern browsers (Chrome, Firefox, Safari, Edge)
- [ ] Responsive design verified on mobile, tablet, desktop
- [ ] Security audit passed
- [ ] Documentation complete and approved
- [ ] Staff training completed with sign-off

---

## 14. Approval and Sign-Off

### 14.1 Stakeholder Approval
| Role | Name | Signature | Date |
|------|------|-----------|------|
| **Client Representative** | | | |
| **Project Sponsor** | | | |
| **Project Manager** | | | |
| **Technical Lead** | | | |

### 14.2 Approval Statement
"By signing below, all parties acknowledge that this Project Proposal accurately reflects the scope, requirements, timeline, and budget for the Bayawan Bai Hotel Management System. All stakeholders agree to the terms outlined in this document and authorize the project to proceed to the next phase."

---

## 15. Appendices

### Appendix A: Glossary of Terms
- **HMS** - Hotel Management System
- **OTA** - Online Travel Agency
- **RBAC** - Role-Based Access Control
- **UAT** - User Acceptance Testing
- **API** - Application Programming Interface
- **CSRF** - Cross-Site Request Forgery
- **SMTP** - Simple Mail Transfer Protocol

### Appendix B: Reference Documents
- Use Case Diagram (`Use_Case_Diagram.md`)
- DFD Level 0 Context Diagram (`DFD_Level0_Context_Diagram.md`)
- DFD Level 1 Process Decomposition (`DFD_Level1_Process_Decomposition.md`)
- Database Schema (available in project repository)

### Appendix C: Contact Information
**Development Team:**  
Email: [team@example.com]  
Phone: [Contact Number]

**Bayawan Bai Hotel:**  
Address: Bayawan City, Negros Oriental, Philippines

---

*End of Project Proposal*
