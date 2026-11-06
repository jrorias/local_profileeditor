# Profile Field Editor (`local_profileeditor`)

**Version 2.1.0 · Works with Moodle 4.1 or newer · Free, open source (GPL v3)**

## What is it? (in one minute)

Profile Field Editor is a plugin for Moodle that lets an administrator **update the profile information
of many users at once** — as easily as editing a spreadsheet.

Instead of opening 500 user profiles one by one, you can:

- edit all users in **one table** (click a cell, type, press Enter), or
- **download an Excel file**, fix the cells that need changing, and **upload it back**.

Before anything is saved, the plugin shows you a **preview** of every change, so mistakes are caught early.

## How does it help admins and support teams?

| Common problem | How the plugin solves it |
|---|---|
| "Staff changed department / job title. I have to update 800 profiles." | Fill in one Excel file, import it, done in minutes. |
| "I made a typo and don't know who changed what." | The **Change log** records who changed what, when, and the old and new value. |
| "I need to set the same value (e.g. Country) for a whole group." | **Bulk edit** sets it for all selected users in one click. |
| "Not every admin should see or change user data." | **Permissions** control who can view, edit and see the log. |
| "I'm away from my desk." | Works in the **Moodle mobile app** too. |
| "Our reports must look like our organisation." | **Branding** page: your name, colours and fonts. |

## Main features (the menu pages)

Find them in **Site administration → Users → Profile field editor**.

| Page | What it does (plain words) |
|---|---|
| **Edit users** | A big table of all users and their profile fields. Click a cell to change it. Enter = save and go down, Tab = save and go right. Can bulk edit ticked users or everyone matching a search. |
| **Import updates** | Upload your filled-in Excel/CSV file. You see a preview (old value → new value, plus any problems). Nothing is saved until you press **Apply**. |
| **Template & guide** | Download the Excel template (already filled with your current users, with drop-down lists and an instructions sheet). |
| **Change log** | A history of every change. Filter by editor, bulk edit or file import. |
| **Columns** | Choose which columns show, their order (drag and drop) and which are read-only. |
| **Branding** | Organisation name, tagline, logo, 12 colours, 6 ready-made themes, fonts, sizes. Live preview. |
| **Settings** | Users per page, template format, optional filters (see below). |

**Mobile app:** people with permission see **Profile field editor** in the app's *More* menu. They can search users,
edit one user, bulk update, and view recent changes. (The site needs mobile web services turned on.)

---

## How to install (step by step, no coding)

You need to be a **Moodle site administrator**.

### Step 1 — Get the plugin file

1. Go to the plugin page on GitHub: <https://github.com/jrorias/local_profileeditor>
2. Click the green **Code** button → **Download ZIP**.
   *(Or, if the project has a **Releases** section, download `local_profileeditor_v2.1.0.zip` from there — this is the easiest.)*

> ⚠️ **Important:** Moodle needs the folder inside the ZIP to be named exactly **`profileeditor`**.
> A ZIP downloaded with the green *Code* button has a folder called `local_profileeditor-main`, which Moodle will reject.
> To fix it: unzip, rename the folder to `profileeditor`, then zip it again (the ZIP should contain one folder named `profileeditor`).
> The ready-made `local_profileeditor_v2.1.0.zip` already has the correct folder name.

### Step 2 — Install in Moodle

1. Log in to Moodle as admin.
2. Go to **Site administration → Plugins → Install plugins**.
3. Drag your ZIP file into the box (or click **Choose a file**).
4. Click **Install plugin from the ZIP file**.
5. Click **Continue**. If it says *"Plugin type: local"*, that is correct.
6. On the next screen click **Upgrade Moodle database now**, then **Continue**.

You should see a success message. The plugin is installed.

### Step 3 — Check it works

1. Go to **Site administration → Users → Profile field editor → Edit users**.
2. You should see a table of users. 🎉

*If you can't upload ZIP files (some hosts disable this):* ask your server administrator to copy the `profileeditor`
folder into `moodle/local/` and then open **Site administration → Notifications**.

---

## How to find it later

**Site administration → Users → Profile field editor**

Moodle may also search it: type "Profile field editor" in the admin search box at the top.

---

## Quick start: update 1,000 users with Excel

1. **Template & guide** → **Download Excel template**.
2. Open it. Change **only the cells that need updating**. Empty cells are ignored. Don't change the username column.
3. **Import updates** → upload the file.
4. Read the preview. Fix problems if any are listed.
5. Click **Apply**. Done — check the **Change log** if you want proof.

Tips: dates can be written as `2026-11-06` or `06/11/2026`; Yes/No fields accept `Yes`, `No`, `1`, `0`.

---

## Where to edit things (for non-technical admins)

You do **not** need to touch any code for the most common changes.

| I want to change… | Go to… |
|---|---|
| Organisation name, colours, fonts, logo | **Profile field editor → Branding** → Save changes |
| Which columns appear, and their order | **Profile field editor → Columns** |
| How many users show per page | **Profile field editor → Settings** |
| Only include users with certain email domains in the template | **Settings** → *Only include these email domains* |
| Leave out test or system accounts | **Settings** → *Leave these accounts out* |
| Who is matched in an import (username / email / ID number) | **Settings** → *Find users by* |
| Wording of buttons and messages | **Site administration → Language → Language customisation** (pick *English*, then search for `local_profileeditor`) |
| Who is allowed to use the plugin | **Site administration → Users → Permissions → Define roles** (look for `profileeditor`) |
| Add a new profile field | **Site administration → Users → Accounts → User profile fields**. It appears in the editor automatically. |

After changing text or settings, if you don't see it yet: **Site administration → Development → Purge caches**.

### Permissions at a glance

| Permission | What it allows | Default |
|---|---|---|
| `local/profileeditor:view` | Open the editor, download templates | Manager |
| `local/profileeditor:edit` | Change values, bulk edit, import | Manager |
| `local/profileeditor:viewlog` | See the change log | Manager |

Only site administrators can edit other site administrators.

---

## For developers (short map)

| File / folder | Purpose |
|---|---|
| `version.php` | Version number — **always raise it after any change** (also refreshes the mobile app). |
| `settings.php` | Admin menu and settings page |
| `index.php`, `import.php`, `guide.php`, `log.php`, `columns.php`, `branding.php` | The six pages |
| `classes/` | Main logic (field manager, importer, template builder, branding, user query) |
| `classes/output/mobile.php`, `db/mobile.php`, `db/services.php` | Mobile app screens and web services |
| `lang/en/local_profileeditor.php` | All text shown to users |
| `styles.css`, `js/` | Look and in-page behaviour |
| `db/access.php` | Permissions |

When you change a web feature, check whether `classes/output/mobile.php` needs the same change.

## Upgrading

Install the new ZIP the same way as above (Site administration → Plugins → Install plugins). Moodle will offer to
upgrade. Your settings, branding and change log are kept.

## Need help?

Open an issue on GitHub: <https://github.com/jrorias/local_profileeditor/issues>

*© 2026 Capytech · License: GNU GPL v3 or later*
