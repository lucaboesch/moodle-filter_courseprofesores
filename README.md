# Course Teachers Filter for Moodle 4+
**Repository:** `moodle-filter_courseprofesores`

A filter plugin for Moodle that replaces the `{courseprofesores}` tag with a visually appealing list of the course teachers, grouped by role. Each teacher card shows their avatar, a direct link to their profile, and a link to send them a message.

## Features

- **Grouped by role**: Teachers are organized according to their role (Editing teacher, Teacher, Manager)
- **Avatar display**: Shows each teacher's profile picture
- **Profile link**: Direct link to each teacher's profile page
- **Messaging link**: Direct link to send a message through Moodle's messaging system
- **Online status**: Indicates whether the teacher is currently online (green dot) or shows their last access to the course, so students know when to contact them
- **Card color options**: Customizable accent color (buttons, borders and highlights) and card background color, with predefined palettes: standard blue, vibrant orange, deep blue/violet and hot pink/red
- **Display styles**: Choose between Cards, List or Compact layouts from the plugin settings
- **Participants link**: Link to the full course participants list
- **Role-based**: Only shows users with teaching roles (editing teacher, teacher, manager), configurable from the settings page
- **Capability-based visibility**: Teachers are shown to users granted the `filter/courseprofesores:viewprofesores` capability (granted by default to students and teaching roles)
- **Context-aware**: Falls back to higher contexts if no teachers are found at course level
- **Privacy compliant**: Does not store personal data (GDPR ready)
- **Bilingual**: English and Spanish support

## How it works

Simply type `{courseprofesores}` anywhere in your course content (page, label, section summary, etc.) and the filter will replace it with a list of all course teachers.

## Installation

1. Download the plugin or clone it from the repository:
   ```bash
   git clone https://github.com/dronix69/filter_courseprofesores.git
   ```

2. Place the `filter_courseprofesores` folder into your Moodle `filter` directory:
   ```
   /path/to/moodle/filter/filter_courseprofesores/
   ```

3. Log in to Moodle as administrator.

4. Go to **Site administration > Notifications** to complete the installation.

5. Go to **Site administration > Plugins > Filters > Manage filters**.

6. Enable the "Course Teachers" filter.

7. Optionally, configure the settings at **Site administration > Plugins > Filters > Course Teachers**.

## Configuration

The plugin provides the following settings at **Site administration > Plugins > Filters > Course Teachers**:

| Setting | Description |
|---------|-------------|
| **Show avatars** | Display teacher avatar images in the teachers list |
| **Show department** | Display teacher department information if available |
| **Show institution** | Display teacher institution information if available |
| **Show message link** | Display a direct link to send a message to the teacher |
| **Show online status** | Display whether the teacher is online now or their last course access |
| **Show participants link** | Display a link to the course participants page |
| **Roles to include** | Select which course roles are displayed as teachers |
| **Display style** | Choose the layout: Cards, List or Compact |
| **Accent color** | Accent color for teacher cards and buttons: Standard blue (#0f6cbf), Vibrant orange (#fc6500), Deep blue/violet (#120ef2) or Hot pink/red (#f20e3f) |
| **Card background color** | Background color for the teacher cards: Theme default, Warm orange (#fc6500), Deep blue (#120ef2) or Hot pink/red (#f20e3f) |

## Usage

### Basic usage

Place the tag anywhere in your course content:

```
{courseprofesores}
```

### Example: Welcome page

```
Welcome to {coursefullname}!

Your teachers for this course are:

{courseprofesores}

If you have any questions, feel free to contact them directly through the message link.
```

### Example: Course description

```
{courseprofesores}

---
Click any teacher's name to view their full profile, or use the message button to contact them directly.
```

## Output structure

The filter generates HTML with the following structure:

```html
<div class="filter-courseprofesores-container">
    <div class="profesores-role-group">
        <h4 class="profesores-role-title">Editing teacher</h4>
        <div class="profesores-list">
            <div class="profesor-card">
                <div class="profesor-avatar">
                    <a href="/user/view.php?id=X&course=Y">
                        <img src="..." class="userpicture" />
                    </a>
                </div>
                <div class="profesor-info">
                    <a href="/user/view.php?id=X&course=Y" class="profesor-name">Full Name</a>
                    <div class="profesor-details">Department, Institution</div>
                    <div class="profesor-actions">
                        <a href="/message/index.php?id=X" class="profesor-action-link message-link">
                            Send message
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="profesores-footer">
        <a href="/user/index.php?id=Y" class="participants-link">
            View all participants
        </a>
    </div>
</div>
```

## CSS classes for customization

| Class | Description |
|-------|-------------|
| `.filter-courseprofesores-container` | Main container |
| `.profesores-role-group` | Container for each role group |
| `.profesores-role-title` | Role title |
| `.profesores-list` | List of teachers within a role |
| `.profesor-card` | Individual teacher card |
| `.profesor-avatar` | Avatar container |
| `.profesor-info` | Teacher details container |
| `.profesor-name` | Teacher name link |
| `.profesor-details` | Department/institution information |
| `.profesor-actions` | Action links container |
| `.message-link` | Message link |
| `.profesores-footer` | Footer with participants link |
| `.participants-link` | Link to the participants page |

## Security and privacy

- All user data is sanitized using Moodle's built-in functions
- Only shows teachers that the current user has permission to view
- Respects Moodle's messaging privacy settings (`can_message_user()`)
- Does not store personal data (GDPR compliant via `null_provider`)
- Deleted users are automatically excluded
- Uses Moodle's capability system for participants link visibility

## Requirements

- Moodle 4.5 or higher (version 2.0.0+, namespaced architecture — compatible with Moodle 5.0+)
- PHP 8.1 or higher

> **Note for Moodle 5.0+:** Since version 2.0.0, the plugin uses the new namespaced
> filter architecture (`\filter_courseprofesores\text_filter`) according to the MDL-82427
> standard, removing all deprecation warnings.

## License

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

The full license text is available in the [LICENSE](LICENSE) file included in the plugin root.

---
**Official repository:** [filter_courseprofesores](https://github.com/dronix69/filter_courseprofesores)
