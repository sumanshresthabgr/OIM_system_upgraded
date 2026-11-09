
Online Invitation Management System (OIMS)
The Online Invitation Management System (OIMS) is a database-driven PHP web application designed to streamline event planning. It provides hosts with an end-to-end workflow to design digital invitations, distribute them via unique guest links, and track real-time RSVP counts and attendee lists from a centralized dashboard ledger.

🔄 Core User Workflow
Plaintext
[ Register/Login ] ──> [ Create & Design Event ] ──> [ Copy Link to Guests ]
                                                              │
[ Manage Ledger ] <── [ Guest Submits RSVP Name ] <───────────┘
1. Host Onboarding & Event Creation
Authentication: Users quickly create an account (register.php) and access their private management hub (dashboard.php).

Design & Customization: Inside the dashboard, hosts can create a new design (create_event.php) and fill out specific event details (dates, venues, timings).

Deployment: After committing the changes (process_event.php), the system generates a custom link which the host can quickly copy via a built-in clipboard helper (clipboard.js) to distribute to their guests.

2. Guest RSVP Experience
Public Interaction: Guests open the shared URL (e.g., view.php?id=84840942).

Response Submission: Guests enter their name directly into the invitation interface and select either ATTEND or DECLINE.

3. Host Ledger & Analytics
Real-Time Tracking: Upon submission, data is instantly pushed to the host’s portal (view_rsvps.php).

Metrics: The host is presented with an automated tally showing the explicit Count of Attending vs. Count of Declining guests.

Guest Management: The ledger provides full administrative control, allowing hosts to review guest names or delete an individual from the guest list entirely to keep numbers updated.


Technical Implementation
Backend Environment: PHP (Native sessions handle host state; POST requests handle guest responses).

Database Ledger: MySQL / MariaDB (Schema initialized via invitation_db.sql tracks guest_name, status [Attend/Decline], and links them to an explicit event_id).

Frontend Design: Flexible HTML5 layouts styled with localized CSS rules (style.css).
