# Sequential Form Plugin

The **Sequential Form** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It implements a single form over a sequence of pages. Think: collect-data, collect-more-data and show video, click to accept user agreement.

## Installation

Installing the Sequential Form plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install sequential-form

This will install the Sequential Form plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/sequential-form`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `sequential-form`. You can find these files on [GitHub](https://github.com/richard-hainsworth/grav-plugin-sequential-form) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/sequential-form

> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error), [Problems](https://github.com/getgrav/grav-plugin-problems)
and [Form](httpsL//github.com/getgrav/grav-plugin-form) to operate.

### Admin Plugin

If you use the admin plugin, you can install directly through the admin plugin by browsing the `Plugins` tab and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/Sequential Form/sequential-form.yaml` to `user/config/plugins/sequential-form.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
built_in_css: true
```

Note that if you use the admin plugin, a file with your configuration, and named sequential-form.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the admin.

## Usage

The use case is a user registration system that required data collection from the user, a safety video to be seen, with confirmation it was seen, a terms and conditions page that required confirmation the terms are agreed to, processing of the data.

A single form is fine to collect data, but the process sequence in the form cannot be delayed.

Two new `Form` processes are provided with this plugin.
1. `sequence` - parameters list the sequence of routes to be followed in order.
1. `next_page` - the process to be included in the subsequent forms.

The plugin also provides the `sequence` template, to be used in the sub pages of the sequence.

## Example

The following is the structure of the user pages:
- 01.start/
    - sequence.md
    - video/
      - sequence.md
    - terms/
        - sequence.md
    - final/
        - formdata.md

The contents of these files is as follows
- 01.start/sequence.md:

```yaml
---
title: User data
sequence:
    banner: true
    content: above
form:   
  name: user-data`  
  fields:
    - name: location
      type: text
      label: Location
    - name: mood
      type: text
      label: Mood
    - name: tool
      type: hidden
      label: Tool
  buttons:
    - type: submit
      value: Start Registration
  process:
      - sequence:
          name: collect-data-sq
          routes:
              - video
              - terms
      - display: start/final
---
    # Collect Data
```
- 01.start/video/sequence.md

```yaml
---
title: Safety Video
sequence:
    banner: true
    name: collect-data-sq
    content: below
form:
    fields:
        - name: tool
          type: text
          Label: Tool
    buttons:
        - type: submit
          value: Watched Safety Video
    process:
        - next_page: true
---
# Safety Video
A safety video
```
- 01.start/terms/sequence.md

```yaml
---
title: Terms & Conditions
sequence:
    banner: true
    name: collect-data-sq
    content: below
form:
    buttons:
        - type: submit
          value: Agree to Conditions
    process:
        - next_page: true
---
** Text of the Contract **
```
- 01.start/final/formdata.md

```yaml
---
title: final
---
```

### Comments

1. To provide for more than one sequence, all sequences must be named.
    - The name of the sequence is given in the `sequence.name` page header.
    - `sequence.name` must be included in the header of every sub-page of the sequence.
    - The `next_page` process must be given the sequence name.
2. `routes` is a mandatory part of the `sequence` process.
  - Each route must correspond to the sub-directory name.
1. In each sub-page, the *Form* process `next_page` must be set to the sequence name.
1. Data can be added through forms on every page, but the fields must be *pre-declared*  in
the first form, with the type 'hidden' so that they are not shown in the first form.
1. A banner is included to show the order of the stages.
    - If no banner is required, then set `sequence.banner: false` in the page header.
    - The class of the banner is `sqform-banner`, which can be set in a custom.css. If this is required, then set `use_built_in_css` to `false` in the plugin configuration.
1. The form content can be set above, or below the page contents.
 - omitting `sequence.content` renders the content above and the form at the bottom.

## To Do

- [ ] Allow for named forms instead of forms in the page header
- [ ] Allow for named sequence stages in banner
- [ ] Styling of stage banner, highlighting the active stage.
