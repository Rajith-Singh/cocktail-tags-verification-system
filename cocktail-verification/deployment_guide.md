# Hostinger Deployment Guide for Cocktail Verification App

This guide provides step-by-step instructions for deploying your PHP application to Hostinger shared hosting.

### **Step 1: Set Up the Database on Hostinger**

1.  **Log in to your Hostinger hPanel.**
2.  Navigate to **Databases -> MySQL Databases**.
3.  Create a new database. Note down the following, as you will need them later:
    *   **MySQL Database Name** (e.g., `u123456789_dbname`)
    *   **MySQL User** (e.g., `u123456789_user`)
    *   **Password** for the user.
4.  Once the database is created, find it in the "List of Current MySQL Databases And Users" and click **Enter phpMyAdmin**.
5.  Inside phpMyAdmin, select your newly created database on the left.
6.  Click the **Import** tab.
7.  Under "File to import", click **Choose File** and select the `database/main_sql_code.sql` file from your project.
8.  Click **Go** at the bottom of the page to start the import. This will create all the necessary tables and data.

### **Step 2: Prepare and Upload Your Project Files**

1.  On your local computer, create a `.zip` archive of your `cocktail-verification` directory.
2.  In your Hostinger hPanel, go to **Files -> File Manager**.
3.  Navigate to the `public_html` directory.
4.  Upload the `.zip` file you created.
5.  Once uploaded, right-click on the `.zip` file and select **Extract**. Choose `.` as the destination to extract the files into a `cocktail-verification` folder within `public_html`.

### **Step 3: Configure Your Application**

1.  In the Hostinger File Manager, navigate to `public_html/cocktail-verification/config/`.
2.  Right-click on `database.php` and select **Edit**.
3.  Update the following lines with the database credentials you noted down in Step 1:

    ```php
    define('DB_HOST', 'localhost'); // Hostinger uses 'localhost' for the database host
    define('DB_NAME', 'your_database_name'); // e.g., u123456789_dbname
    define('DB_USER', 'your_database_user'); // e.g., u123456789_user
    define('DB_PASS', 'your_database_password');
    ```

4.  You also need to update the `SITE_URL`. Replace `http://localhost:8001/` with the actual URL of your website. Make sure to include the trailing slash.

    ```php
    define('SITE_URL', 'https://yourdomain.com/cocktail-verification/public/'); 
    ```
    *Note: The final URL might be different depending on how you set up your domain. We will adjust this if needed in the next step.*

5.  Save and close the file.

### **Step 4: Set the Document Root (Very Important)**

For security and cleaner URLs, your domain should point to the `public` directory inside your `cocktail-verification` folder, not the root folder.

1.  In hPanel, go to **Domains -> Subdomains** (or **Domains** if you are using your main domain).
2.  If you're using a subdomain (e.g., `cocktails.yourdomain.com`), you can set the document root when you create it. The path should be `/public_html/cocktail-verification/public`.
3.  If you are using your main domain, or a subdomain that is already created, you may need to edit the document root. If you can't find this setting, you might need to contact Hostinger support to have them change the document root for your domain to `/public_html/cocktail-verification/public`.
4.  **Alternative (if you can't change the document root):** You can move the contents of the `public` directory to the root of your subdomain (e.g. `public_html/subdomain/`) and adjust the paths in your config and other files accordingly. This is more complex and not recommended if it can be avoided.

### **Step 5: Final Testing**

1.  Open your web browser and navigate to your domain (e.g., `https://yourdomain.com`).
2.  If you configured the document root correctly, your application's login page should appear.
3.  Try to log in and use the application to ensure everything is working correctly.

If you encounter any errors, you can enable debugging in `config/database.php` by ensuring `define('DB_DEBUG', true);` is set. This might provide more information about the problem. Remember to turn it off once you're done.
