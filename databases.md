# database design
Describes the database structure required for this project

## database: `adl_test`

### table: `main`
holds the list of apps and libs being uploaded.

#### columns
* id (`int(11)`, `PRIMARY`, `AUTO_INCREMENT`)
* name (`varchar(25)`)
* authors (`tinytext`)
* type (`varchar(3)`)
* version (`varchar(11)`)
* file (`varchar(100)`)
* user (`varchar(15)`)
* description (`text`)
* uploaded (`datetime`)
* tags (`tinytext`)
* default_include (`BOOLEAN`, default: `0`)

### table: `users`
holds the list of users, a sha256-hash of the password and the privileges they have.

#### columns
* name (`varchar(25)`)
* pw (`varchar(64)`)
* privileges (`int(1)`, default: `0`)