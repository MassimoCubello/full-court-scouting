# full-court-scouting
A web-based basketball scouting report management system built with PHP and MySQL.

## Description

Full Court Scouting is a web-based application designed for basketball scouts, coaches, analysts, and program directors who need a structured and efficient way to evaluate and compare players.

The system allows scouts to create detailed scouting reports for individual players. Each report contains both quantitative ratings (such as shooting, defence, athleticism, and playmaking) and qualitative observations (such as strengths, areas for improvement, games watched, player comparisons, and general notes).

The system automatically calculates an overall rating based on attribute scores, allowing scouts to quickly compare players and identify top prospects. The most recent report is used to display a player's current rating, while historical reports remain accessible for review.

The application supports full CRUD functionality for scouting reports and ensures that Scouts can only edit or delete their own reports while maintaining read access to all reports in the system. The goal of the application is to streamline the scouting workflow by centralizing player evaluations, improving consistency in assessments, and enabling data-driven decision-making in basketball recruitment and analysis.

## Features
- User authentication (register/login/logout)
- Player database management
- Scouting report creation with attribute ratings
- Automated overall rating calculation
- Player filtering, sorting, and search

## Tech Stack
- Frontend: HTML, CSS
- Backend: PHP
- Database: MySQL (managed through phpMyAdmin)
- Server Environment: MAMP