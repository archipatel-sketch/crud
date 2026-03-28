# Laravel Dynamic CRUD Package

A Laravel package to perform **dynamic CRUD operations** with configurable form fields and table-based routing. This package is ideal for rapidly building CRUD interfaces for any table in your Laravel application.


---

##  Features

- **Dynamic CRUD Operations:** Create, read, update, and delete records dynamically.
- **Configurable Form Fields:** Define fields, input types, validation rules, and visibility in `config/form-fields.php`.
- **Dynamic Table Fetching:** Handles multiple tables dynamically via URL.
- **Graceful Error Handling:** Throws a `TableNotFoundException` for non-existent tables.
- **DataTables Integration:** Displays data in responsive DataTables with configurable visibility.
- **Supports Input Types:** `text`, `password`, `email`, `image`, `textarea`, `date`, `number`.

---

##  Installation

1. **Require the package via Composer:**

```
composer require archipatel-sketch/crud:dev-main
```


2. ** Configuration**

Define your table fields in config/form-fields.php. Each table name should have an array of field definitions.

Example form-fields.php for users table:
```
<?php

return [

    'users' => [

        [
            'label' => 'Name',
            'name' => 'name',
            'type' => 'text',
            'rules' => 'required|string|max:255',
            'visible' => true,
        ],

        [
            'label' => 'Email',
            'name' => 'email',
            'type' => 'email',
            'rules' => 'required|email',
            'visible' => true,
        ],

        [
            'label' => 'Image',
            'name' => 'image',
            'type' => 'file',
            'upload_type' => 'single', // 'single' or 'multiple'
            'rules' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'visible' => true,
        ],

        [
            'label' => 'Password',
            'name' => 'password',
            'type' => 'password',
            'rules' => 'required|min:6',
            'visible' => false, // Hidden in DataTables
        ],

    ],

    // Define additional tables here
    // 'products' => [ ... ],

];
```
👉 **Field Definition Parameters**

| Parameter     | Description                                                                 |
| ------------- | --------------------------------------------------------------------------- |
| `label`       | Display name in forms and table headers                                     |
| `name`        | Database column name                                                        |
| `type`        | Input type (`text`, `email`, `password`, `file`, `image`, `textarea`, etc.) |
| `rules`       | Laravel validation rules                                                    |
| `visible`     | Show column in DataTables (`true` / `false`)                                |
| `upload_type` | For `file` or `image` fields: `'single'` or `'multiple'`                    |

👉 **Publish Config File**

Run the following command to publish the query builder configuration file:
```
php artisan vendor:publish --tag=config
```

👉 **Run Migrations**

Before using the package, run the following command to migrate the required database tables:
```
php artisan migrate --path=vendor/archipatel-sketch/crud/src/Database/migrations
```

👉 **Database Configuration for Query Management**

Set the following variables in your .env file to configure the database used for creating and managing queries:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

```
👉 **Set package providers**
```
php artisan vendor:publish --provider="ArchipatelSketch\Crud\Providers\CrudServiceProvider"
```

3. **Routes**

Include the package routes in your routes/web.php:

add prefix if you want

```
Route::group(['prefix' => 'crud'], function () {
    include base_path('vendor/archipatel-sketch/crud/src/Routes/web.php');
});
```

4. ** Usage**

Create your database tables matching the names defined in form-fields.php.

Access CRUD operations via URL:
http://your-app.test/{table_name}

```
http://127.0.0.1:8000/users
```
if you add the prefix in then
```
http://127.0.0.1:8000/crud/users
```

👉 CrudController reads the table name from the URL.

👉 Fetches field definitions from config/form-fields.php.

👉 Throws TableNotFoundException if table is not defined.

👉 Renders forms for create/edit and displays data using DataTables.

##  Example Configuration for posts Table
```
'posts' => [
    [
        'label' => 'Title',
        'name' => 'title',
        'type' => 'text',
        'rules' => 'required|string|max:255',
        'visible' => true,
    ],
    [
        'label' => 'Content',
        'name' => 'content',
        'type' => 'textarea',
        'rules' => 'required|string',
        'visible' => true,
    ],
    [
        'label' => 'Featured Image',
        'name' => 'featured_image',
        'type' => 'image',
        'upload_type' => 'single',
        'rules' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'visible' => true,
    ],
    [
        'label' => 'Published At',
        'name' => 'published_at',
        'type' => 'date',
        'rules' => 'nullable|date',
        'visible' => true,
    ],
],
```

##  Example Usage
👉 Access users CRUD: http://your-app.test/crud/users

👉 Access posts CRUD: http://your-app.test/crud/posts

👉 If the table does not exist in form-fields.php, a 404 Table Not Found error is returned.

## ⚠ Notes
Table names in form-fields.php must exactly match database table names.
For file/image uploads, ensure storage permissions and proper configuration in config/filesystems.php.
CrudServiceProvider must be registered (or auto-discovered) for the package to work.
Make sure package routes are included in your application’s web.php.
