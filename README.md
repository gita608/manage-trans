# ManageTrans - Transportation Management System

ManageTrans is a web-based application designed to streamline and manage transportation operations. It provides a centralized platform for managing drivers, vessels, trips, and staff, ensuring efficient and organized operations.

## Key Features

*   **Dashboard:** Provides a comprehensive overview of all transportation activities.
*   **Driver Management:** Add, edit, and view driver information.
*   **Vessel Management:** Manage vessel details, including their capacity and specifications.
*   **Trip Management:** Plan and track trips, including assigning drivers and vessels.
*   **Staff Management:** Manage staff access and roles within the application.
*   **User Authentication:** Secure login and registration for authorized personnel.
*   **Activity Logging:** Tracks all major activities within the system for accountability.

## Technologies Used

*   **Backend:** Laravel 12, PHP 8.2
*   **Frontend:** JavaScript, Bootstrap CSS
*   **Database:** SQLite (by default)

## Installation and Setup

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-username/manage-trans.git
    cd manage-trans
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3.  **Set up the environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Configure your database:**
    *   Update the `DB_*` variables in your `.env` file.
    *   For SQLite, simply create an empty file: `touch database/database.sqlite`

5.  **Run database migrations:**
    ```bash
    php artisan migrate
    ```

6.  **Build frontend assets:**
    ```bash
    npm run build
    ```

## Running the Application

To run the application in a development environment, you can use the following command:

```bash
composer run dev
```

This will start the following services:
*   PHP's built-in web server
*   A queue worker
*   The Pail log viewer
*   The Vite asset bundler

## Testing

To run the application's test suite, use the following command:

```bash
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a pull request or open an issue.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).