# SamplePlugin
This repo provides a starting point to create custom user plugins for RaspAP.

<img width="456" height="404" alt="image" src="https://github.com/user-attachments/assets/058a2044-746d-434a-b5b0-915b5d4c9b95" />

The `SamplePlugin` implements a `PluginInterface` and is automatically loaded by RaspAP's `PluginManager`. Several common plugin functions are included, as well as an example method for persisting data in plugin instances. Each plugin has its own namespace, meaning that classes and functions are organized to avoid naming conflicts. Plugins are self-contained and render templates from inside their own `/templates` directory.

## Contents
 - [Usage](#usage)
 - [Scope of functionality](#scope-of-functionality)
 - [Customizing](#customizing)
 - [Multiple instances](#multiple-instances)
 - [Publishing your plugin](#publishing-your-plugin)

## Usage
The `SamplePlugin` requires an installation of [RaspAP](https://github.com/RaspAP/raspap-webgui), either via a [pre-built OS image](https://docs.raspap.com/quick_start/#pre-built-image), 
with the [Quick installer](https://docs.raspap.com/quick/) or a [Docker container](https://docs.raspap.com/docker/). The default application path `/var/www/html` is used here. If you've chosen a different install location, substitute this in the steps below.

1. Begin by creating a fork of this repository.
2. Change to the install location and create a `/plugins` directory.
   ```
   cd /var/www/html
   sudo mkdir plugins
   ```
3. Change to the `/plugins` directory and clone your `SamplePlugin` fork:
   ```
   cd plugins
   sudo git clone https://github.com/[your-username]/SamplePlugin
   ```
4. The `PluginManager` will detect and autoload the plugin; a new **Sample Plugin** item will appear in the sidebar.

## Scope of functionality
The `SamplePlugin` implements the server-side methods needed to support basic plugin functionality. It initalizes a `Sidebar` object and adds a custom navigation item. User input is processed with `handlePageAction()` and several common operations are performed, including:

1. Saving plugin settings
2. Starting a sample service
3. Stopping a sample service

Template data is then collected in `$__template_data` and rendered by the `main.php` template file located in `/templates`. Property get/set methods are demonstrated with `apiKey` and `serviceStatus` values. A method is then used in `persistData()` to save the `SamplePlugin` object data.

> Importantly, SamplePlugin does _not_ use the PHP `$_SESSION` object. Known as a "superglobal", or automatic global variable, this is available in all scopes throughout a script. Using the `$_SESSION` object in a plugin context can lead to conflicts with other plugin instances.

On the front-end, Bootstrap's [form validation](https://getbootstrap.com/docs/5.3/forms/validation/) is used to validate user input. A custom JavaScript function responds to a click event to generate a random `apiKey` value. The `sample.service` LED indicator is functional, as are the service stop/start form buttons.

## Customizing
The `SamplePlugin` demonstrates basic plugin functions without being overly complex. It's designed with best practices in mind and made to be easily modified by developers.

### Unique plugin names
Most plugin authors will probably begin by renaming `SamplePlugin` to something unique. The `PluginManager` expects the plugin folder, file, namespace and class to follow the same naming convention. When renaming the `SamplePlugin` ensure that each of the following entities uses the same plugin name:


|  Entity                                          |   Type     |
|--------------------------------------------------|------------|
| `plugins/SamplePlugin`                           | folder     |
| `plugins/SamplePlugin/SamplePlugin.php`          | file       |
| `namespace RaspAP\Plugins\SamplePlugin`          | namespace  |
| `class SamplePlugin implements PluginInterface`  | class      |

That is, replace each occurrence of `SamplePlugin` with your plugin name in each of these entities.

### Plugin logic and templates
Plugin classes and functions are contained in `SamplePlugin.php`. The parent template `main.php` and child tab templates are used to render template data. 

```
├── SamplePlugin/
│   ├── SamplePlugin.php
│   └── templates/
│       ├── main.php
│       └── tabs/
│           ├── about.php
│           ├── basic.php
│           └── status.php
```

You may wish to omit, modify or create new tabs. This is done by editing `main.php` and modifying the contents of the `/tabs` directory.

### Sidebar item
The `PluginInterface` exposes an `initalize()` method that is used to create a unique sidebar item. The properties below can be customized for your plugin:

```
$label = _('Sample Plugin');
$icon = 'fas fa-plug';
$action = 'plugin__'.$this->getName();
$priority = 65;
```

You may specify any icon in the [Font Awesome 6.6 free library](https://fontawesome.com/icons) for the sidebar item. The priority value sets the position of the item in the sidebar (lower values = a higher priority).

### Permissions
For security reasons, the `www-data` user which the `lighttpd` web service runs under is not allowed to start or stop daemons or execute commands. RaspAP's installer adds the `www-data` user to [sudoers](https://www.sudo.ws/about/intro/), but with restrictions on what commands the user can run. If your plugin requires execute permissions on a Linux binary not present in RaspAP's [sudoers file](https://github.com/RaspAP/raspap-webgui/blob/master/installers/raspap.sudoers), you will need to add this yourself. To edit this file, the `visudo` command should be used. This tool safely edits `sudoers` and performs basic validity checks before installing the edited file. Execute `visudo` and edit RaspAP's sudoers file like so:

```
sudo visudo /etc/sudoers.d/090_raspap
```

An example of adding entries to support a plugin's service is shown below:

```
www-data ALL=(ALL) NOPASSWD:/bin/systemctl start sample.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl stop sample.service
www-data ALL=(ALL) NOPASSWD:/bin/systemctl status sample.service
```

Wildcards ('`*`') and regular expressions are supported by `sudoers` but [care should be taken when using them](https://www.sudo.ws/posts/2022/03/sudo-1.9.10-using-regular-expressions-in-the-sudoers-file/).

## Multiple instances
The `PluginManager` is a managerial class responsible for locating, instantiating and coordinating plugins. Through the use of namespaces and object data persistence in `SamplePlugin`, any number of user plugins may be installed to `/plugins` and run concurrently.

<img width="619" alt="multiple-highlight" src="https://github.com/user-attachments/assets/2d156efe-8cfc-49e7-b682-219d2db4eeee">

As previously noted, developers should avoid using PHP's `$_SESSION` object in their plugins to prevent conflicts with other plugin instances. 

> The `persistData()` function writes serialized data to the volatile `/tmp` directory which is cleared on each system boot. For this reason, it should _not_ be used as a method of permanent data storage. However, this functionality roughly approximates PHP's `$_SESSION` object; the difference being that each plugin's data is isolated from other plugin instances.

## Publishing your plugin
The `SamplePlugin` contains an "About" tab where you may provide author information, a description and link to your project. If you'd like your plugin to be included in RaspAP's official [plugins repo](https://github.com/RaspAP/plugins) and made available via the one-click Plugin Installer UI, the `manifest.json` file must be populated. This is described in the next section.

### Manifest
The plugin [manifest](https://github.com/RaspAP/SamplePlugin/blob/master/manifest.json) is used to specify basic metadata about your plugin such as its name, version, description, author and so on. If your plugin has external dependencies, configuration files, needs special permissions or requires a non-priviledged user, these may also be defined in their respective fields. Refer to the [SocksProxy plugin manifest](https://github.com/billz/SocksProxy/blob/master/manifest.json) for a working example.

If you've authored a plugin you feel would be useful to the RaspAP community, you're encouraged to open a pull request in the official [plugins repository](https://github.com/RaspAP/plugins). Approved third-party plugins will be made available to install via RaspAP's interface. As a small token of gratitude, authors whose plugins are approved will receive complimentary lifetime access to [Insiders](https://docs.raspap.com/insiders/).
