-- ============================================================
-- C Cube Event Management System - Seed Data
-- Run AFTER schema.sql
-- ⚠️  For local development only. Change admin password before deployment.
-- ============================================================

USE ccube_event_management;

-- ─── DEFAULT SETTINGS ────────────────────────────────────────
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name',           'C Cube'),
('site_tagline',        'Where Innovation Meets Competition'),
('contact_email',       'hello@ccube.dev'),
('contact_phone',       '+92-300-0000000'),
('contact_address',     'Pakistan'),
('facebook_url',        '#'),
('instagram_url',       '#'),
('twitter_url',         '#'),
('linkedin_url',        '#'),
('payment_instructions','Please transfer the registration fee to the following account:\n\nBank: HBL\nAccount Title: C Cube Events\nAccount Number: 0000-0000000-00\nIBAN: PK00HABB0000000000000000\n\nAfter transfer, submit your transaction reference number below.'),
('payment_currency',    'PKR'),
('maintenance_mode',    '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ─── CATEGORIES ──────────────────────────────────────────────
INSERT INTO categories (name, slug, description, icon) VALUES
('Programming',           'programming',           'Competitive coding, algorithms, and data structures contests.',       'fa-code'),
('Hackathons',            'hackathons',            '24–48 hour intensive collaborative problem-solving events.',           'fa-laptop-code'),
('Web Development',       'web-development',       'Front-end, back-end, and full-stack design challenges.',              'fa-globe'),
('Artificial Intelligence','artificial-intelligence','ML, deep learning, and AI application competitions.',               'fa-brain'),
('Engineering',           'engineering',           'Hardware, robotics, and engineering design competitions.',            'fa-cogs'),
('Creative Competitions', 'creative-competitions', 'UI/UX design, graphic design, and creative challenge events.',        'fa-paint-brush')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ─── DEMO ADMIN ACCOUNT ──────────────────────────────────────
-- Password: Admin@CCube2025  (bcrypt hash – CHANGE BEFORE PRODUCTION)
INSERT INTO users (full_name, email, password_hash, phone, institution, role, account_status) VALUES
('C Cube Admin', 'admin@ccube.dev',
 '$2y$12$y815eyrTXcOfSvMMTDdy8.d954QTrMBTk680TN52OyVJf2Kw2qBlG',
 '+92-300-0000000', 'C Cube HQ', 'admin', 'active')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name);

-- ─── DEMO PARTICIPANT ─────────────────────────────────────────
-- Password: Test@1234
INSERT INTO users (full_name, email, password_hash, phone, institution, role, account_status) VALUES
('Ali Hassan', 'ali@example.com',
 '$2y$12$izpOwWU2J55hfYFLY5AF7OkUQX.PE4AI3PL90GrEIctuqv6PDfncC',
 '+92-321-1234567', 'FAST NUCES Lahore', 'participant', 'active')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), full_name = VALUES(full_name);

-- ─── SAMPLE EVENTS ───────────────────────────────────────────
INSERT INTO events (category_id, title, slug, short_description, description, event_date, registration_deadline, venue, registration_fee, capacity, minimum_team_size, maximum_team_size, eligibility, rules, prize_information, status) VALUES

(1, 'CodeStorm 2025',
 'codestorm-2025',
 'A high-intensity 5-hour competitive programming contest testing speed, accuracy, and algorithmic thinking.',
 '<p>CodeStorm 2025 is C Cube''s flagship competitive programming event. Participants will face 8–12 algorithmic problems of varying difficulty, ranging from implementation challenges to advanced graph theory and dynamic programming.</p><p>All solutions will be judged by an automated online judge. Partial scoring is not available – only accepted (AC) submissions count.</p>',
 '2025-11-15 09:00:00', '2025-11-10 23:59:00',
 'CS Department Auditorium, Hall B', 500.00, 80, 1, 1,
 'Open to all university students with a valid student ID.',
 '1. No plagiarism.\n2. Internet access is restricted.\n3. Only C, C++, Java, or Python are allowed.\n4. Judges'' decisions are final.',
 '1st Place: PKR 15,000\n2nd Place: PKR 10,000\n3rd Place: PKR 5,000\nCertificates for all participants.',
 'published'),

(2, 'HackCube 48',
 'hackcube-48',
 'A 48-hour hackathon where teams build innovative solutions to real-world social and technical problems.',
 '<p>HackCube 48 brings together the brightest minds to collaborate, innovate, and build within 48 hours. Teams will select a challenge track and present a working prototype to a panel of industry judges.</p><p>Mentors from leading tech companies will be available throughout the event. Meals, snacks, and workspace will be provided.</p>',
 '2025-12-05 08:00:00', '2025-11-28 23:59:00',
 'C Cube Innovation Hub, Main Campus', 1000.00, 60, 2, 4,
 'Open to undergraduate and postgraduate students across Pakistan.',
 '1. Teams of 2–4 members.\n2. All code must be written during the event.\n3. Open-source libraries are permitted.\n4. Teams must present a working demo.',
 '1st Place: PKR 50,000\n2nd Place: PKR 30,000\n3rd Place: PKR 15,000\nBest UI/UX: PKR 10,000\nAll teams receive certificates.',
 'published'),

(3, 'WebWiz Championship',
 'webwiz-championship',
 'Design and develop a full web application within 6 hours, judged on design, functionality, and code quality.',
 '<p>WebWiz Championship challenges participants to design and build a full web application from scratch based on a problem statement revealed at the start of the event. Judging criteria include responsiveness, code quality, user experience, and creativity.</p>',
 '2025-11-22 10:00:00', '2025-11-18 23:59:00',
 'Computer Lab C-301, Engineering Block', 700.00, 40, 1, 2,
 'Students enrolled in any CS, IT, or Software Engineering program.',
 '1. Teams of 1 or 2.\n2. Internet access for documentation and libraries only.\n3. AI coding assistants are not permitted.\n4. Submissions via GitHub.',
 '1st Place: PKR 20,000\n2nd Place: PKR 12,000\n3rd Place: PKR 6,000',
 'published'),

(4, 'AI Challenge: Build the Future',
 'ai-challenge-build-the-future',
 'Develop an AI-powered application that addresses a real-world problem in healthcare, education, or agriculture.',
 '<p>This competition invites teams to build AI/ML-powered applications addressing real-world challenges. Teams will pitch their solutions to a panel of AI researchers and industry professionals.</p><p>Access to cloud compute credits will be provided to shortlisted teams.</p>',
 '2025-12-20 09:00:00', '2025-12-10 23:59:00',
 'Online + Final Presentations at C Cube HQ', 800.00, 50, 2, 3,
 'Open to all students and recent graduates (within 2 years of graduation).',
 '1. Teams of 2–3.\n2. Pre-built models must be disclosed.\n3. Final submission includes code repo and a 5-minute demo video.\n4. Plagiarism results in disqualification.',
 '1st Place: PKR 40,000\n2nd Place: PKR 25,000\n3rd Place: PKR 10,000\nInternship opportunities with sponsor companies.',
 'published'),

(5, 'RoboWars 2025',
 'robowars-2025',
 'Design, build, and battle your robot in C Cube''s exciting annual robotics combat competition.',
 '<p>RoboWars 2025 returns with bigger arenas and fiercer competition. Teams will design and build combat robots within a specified weight class. Robots will compete in a round-robin knockout format.</p>',
 '2026-01-15 10:00:00', '2025-12-31 23:59:00',
 'Engineering Workshop, Robotics Arena', 1500.00, 24, 2, 4,
 'Open to engineering students with access to workshop facilities.',
 '1. Robot weight limit: 10 kg.\n2. No liquid weapons.\n3. Remote control must be 2.4 GHz.\n4. Safety standards must be followed.',
 '1st Place: PKR 60,000 + Trophy\n2nd Place: PKR 35,000\n3rd Place: PKR 15,000',
 'published'),

(6, 'UI Flux Design Sprint',
 'ui-flux-design-sprint',
 'A 4-hour UI/UX design sprint where creativity and user empathy drive beautiful, functional interface designs.',
 '<p>UI Flux challenges designers to create a fully functional, high-fidelity prototype for a given brief within 4 hours. Entries will be judged on usability, aesthetics, innovation, and presentation quality.</p>',
 '2025-11-30 09:00:00', '2025-11-25 23:59:00',
 'Design Studio, Creative Arts Building', 400.00, 30, 1, 1,
 'Open to students studying design, CS, IT, or any related field.',
 '1. Individual competition only.\n2. Tools: Figma, Adobe XD, or Sketch.\n3. Assets may be sourced from free libraries with attribution.\n4. Prototype must be interactive.',
 '1st Place: PKR 12,000\n2nd Place: PKR 8,000\n3rd Place: PKR 4,000\nAll participants receive digital certificates.',
 'published');

-- ─── DEMO REGISTRATIONS (for participant "Ali Hassan") ────────
-- We reference the IDs after insert. Assume user id=2, events id=1,2
SET @ali_id = (SELECT id FROM users WHERE email='ali@example.com' LIMIT 1);
SET @ev1    = (SELECT id FROM events WHERE slug='codestorm-2025' LIMIT 1);
SET @ev2    = (SELECT id FROM events WHERE slug='hackcube-48' LIMIT 1);

INSERT INTO registrations (registration_reference, user_id, event_id, participation_type, registration_status) VALUES
('CCR-20251001-0001', @ali_id, @ev1, 'individual', 'pending_payment'),
('CCR-20251001-0002', @ali_id, @ev2, 'team',       'pending_approval')
ON DUPLICATE KEY UPDATE registration_status = VALUES(registration_status);

-- Seed a payment record for the first registration
SET @reg1 = (SELECT id FROM registrations WHERE registration_reference='CCR-20251001-0001' LIMIT 1);
SET @reg2 = (SELECT id FROM registrations WHERE registration_reference='CCR-20251001-0002' LIMIT 1);

INSERT INTO payments (registration_id, transaction_reference, amount, payment_status, submitted_at) VALUES
(@reg1, 'TXN-DEMO-001', 500.00, 'pending',  NOW())
ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status);

INSERT INTO payments (registration_id, transaction_reference, amount, payment_status, submitted_at) VALUES
(@reg2, 'TXN-DEMO-002', 1000.00, 'verified', NOW())
ON DUPLICATE KEY UPDATE payment_status = VALUES(payment_status);

-- Update second registration to pending_approval (payment done)
UPDATE registrations SET registration_status='pending_approval' WHERE registration_reference='CCR-20251001-0002';

-- ─── SAMPLE NOTIFICATIONS ────────────────────────────────────
INSERT INTO notifications (user_id, title, message) VALUES
(@ali_id, 'Registration Confirmed', 'Your registration for CodeStorm 2025 (Ref: CCR-20251001-0001) has been received. Please submit your payment to proceed.'),
(@ali_id, 'Payment Verified',       'Your payment for HackCube 48 has been verified. Your registration is now under review by our team.');
