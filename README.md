# PHP Login Application

This is a simple PHP login application that demonstrates user authentication using pure PHP. The application includes features for user login, logout, and a basic dashboard.

## Project Structure

```
gestao_ponto
├── public
│   ├── index.php          # Entry point of the application
│   ├── login.php          # Handles login form submission and authentication
│   ├── logout.php         # Handles user logout functionality
│   └── css
│       └── styles.css     # CSS styles for the application
├── src
│   ├── Auth.php           # Class for user authentication
│   ├── Database.php       # Class for database connections and queries
│   └── User.php           # Class representing user data and operations
├── views
│   ├── header.php         # HTML for the header section
│   ├── footer.php         # HTML for the footer section
│   ├── login.php          # HTML for the login form
│   └── dashboard.php      # HTML for the user dashboard
├── config
│   └── config.php         # Configuration settings (e.g., database connection)
├── sql
│   └── schema.sql         # SQL schema for creating necessary database tables
├── .htaccess              # URL rewriting and server configurations
├── composer.json          # Composer configuration file for dependencies
└── README.md              # Project documentation
```

## Installation

1. Clone the repository or download the files.
2. Set up a web server (e.g., Apache) and point it to the `public` directory.
3. Create a database and run the SQL schema located in `sql/schema.sql` to set up the necessary tables.
4. Update the database connection settings in `config/config.php`.
5. Access the application through your web browser.

## Usage

- Navigate to `public/index.php` to access the application.
- Use the login form to authenticate users.
- After logging in, users will be redirected to the dashboard.
- Users can log out using the logout functionality.

## Contributing

Feel free to fork the repository and submit pull requests for any improvements or features.