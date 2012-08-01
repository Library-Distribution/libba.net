# database design
Describes the database structure required for this project.
For local testing, name the database `adl_test`.

**Note:** All database and table names are defined in 2 files, `api/db.php` and `db2.php`,
so they can be changed easily.

## backend
### table: `main`
holds the list of apps and libs being uploaded.

#### columns
* id (`binary(16)`, `PRIMARY`)
* name (`varchar(25)`)
* type (`varchar(3)`)
* version (`varchar(50)`)
* file (`varchar(100)`)
* user (`binary(16)`)
* description (`text`)
* uploaded (`datetime`)
* tags (`tinytext`)
* default_include (`BOOLEAN`, default: `0`)
* reviewed (`tinyint(1)`, default: `0`)

### table: `users`
holds the list of users, a sha256-hash of the password and the privileges they have.

#### columns
* id (`binary(16)`, `PRIMARY`)
* name (`varchar(25)`, `UNIQUE`)
* mail (`varchar(25)`, `UNIQUE`)
* pw (`varchar(64)`)
* privileges (`int(1)`, default: `0`)
* joined (`date`)
* activationToken (`int(11)`)

## website
### table: `site_user_profile`
holds user profile data specific to the web UI, unrelated to the data backend.

#### columns
* id (`binary(16)`, `PRIMARY`)
* mail (`varchar(25), `UNIQUE`)
* show_mail (`ENUM('hidden', 'members', 'public')`)
* allow_mails (`ENUM('moderators', 'members', 'public')`)
* site_theme (`ENUM('default')`)