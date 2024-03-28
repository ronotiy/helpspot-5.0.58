# Attachments

This library handles some nitty-gritty of dealing with binary files across the 3 supported databases.

SqlServer in particular requires using raw PDO objects and setting parameters for reading and writing binary files to and from the database.

## Attachments Are **NOT** Only Handled Here!

Don't be too confused about the presence of this library.

Attachments (re: `HS_Documents` and binary files) are handled in a few places in HelpSpot:

1. **HS\Attachments** - This library handles reading/writing binaries to/from the database, primarily for the command to convert database binaries to file storage.
2. **Installation/Updating** - The `HS\Install\Tables\Copier` namespace handles copying binary files from one database to another during update process.
    * There's some posisble duplicate code here, as the `Copier` classes go down to raw PDO as well.
3. **Regular Operation** - HelpSpot's day-to-day operation handles file attachments in the core libraries found in `helpspot/lib`.