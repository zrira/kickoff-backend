# Kickoff API Backend

This is the core API service for the Kickoff sports matchmaking platform, built with Laravel. It handles player statistics, venue management, real-time match lifecycle events, and competitive rating (ELO) calculations.

## Technology Stack
- **Framework**: Laravel 11
- **Language**: PHP 8.3+
- **Database**: SQLite (Local development) / PostgreSQL (Production)
- **Real-time**: Laravel Reverb / Pusher
- **Observability**: Laravel Telescope

## Architecture Overview

The backend follows a service-oriented approach within the Laravel framework to separate business logic from the HTTP layer.

### Core Components

- **Models (app/Models)**: Implements the data schema and Eloquent relationships. Key models include Game (Matches), User, Venue, and PlayerProfile.
- **Services (app/Services)**: Contains the core business logic.
    - **EloService**: Responsible for calculating rating changes based on match results and updating historical records.
    - **MatchmakingService**: Manages the automated formation of matches and team balancing logic.
- **Controllers (app/Http/Controllers/Api/V1)**: Handles API requests. Split between Admin controllers for the backoffice and Public controllers for the mobile application.
- **Resources (app/Http/Resources)**: Transforms Eloquent models into structured JSON responses, ensuring consistent API contracts.
- **Jobs (app/Jobs)**: Handles background tasks such as lobby expiration checks and asynchronous Elo updates.

## Key Features

### Competitive Ranking System
The backend implements a sophisticated ELO-based ranking system. Every match result triggers an automated recalculation of player ratings, which are persisted in a historical log to track player progression over time.

### Venue Management
Provides comprehensive tools for managing sports venues, including status toggling (Active/Inactive) and occupancy tracking through daily/weekly schedule endpoints.

### Match Lifecycle
Manages the complete lifecycle of a sports match:
1. Waiting: Match created, looking for players.
2. Lobby: Minimum player count reached, teams being formed.
3. Active: Match is currently being played.
4. Scoring: Match finished, awaiting score submission.
5. Completed: Results approved and rankings updated.

## Setup Instructions

1. Clone the repository and navigate to the directory.
2. Install dependencies: `composer install`
3. Configure environment: `cp .env.example .env`
4. Generate application key: `php artisan key:generate`
5. Run migrations and seed the database: `php artisan migrate --seed`
6. Start the development server: `php artisan serve`

The default seeder creates a superadmin account (`superadmin@kickoff.com` / `secret`) and 100 players with generated match histories for testing.
