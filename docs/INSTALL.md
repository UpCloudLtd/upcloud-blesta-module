# UpCloud Blesta Module Installation Guide

This guide will walk you through the process of installing and configuring the UpCloud VPS Module for Blesta.

## Installation

### Downloading and Installing the Module

1. Obtain the module from the UpCloud GitHub repository.
2. Extract the module files to your local system.
3. Upload the entire module folder to your Blesta installation directory at `/path-to-your-blesta/components/modules/upcloud/` on your Blesta server.
4. Ensure proper file permissions are set for the module directory.

### Obtaining API Credentials

The Blesta module uses UpCloud API tokens for interacting with the cloud platform.

Follow the instructions first to [install the upctl command-line tool](https://upcloudltd.github.io/upcloud-cli/) to your machine.

Then proceed with the instructions to [create a token](https://upcloudltd.github.io/upcloud-cli/commands_reference/upctl_account/token/create/).

### Adding Your First UpCloud Account

1. Log in to your Blesta admin panel.
2. Navigate to **Settings** > **Modules**.
3. Find "UpCloud VPS" in the available modules list and click **Install**.
4. After installation, click **Manage** next to the UpCloud VPS module.
5. Click **Add Account** to create your first UpCloud server connection.
6. Fill in the following details:
   - **Account Name**: A descriptive name for this UpCloud account (e.g., "UpCloud Production")
   - **API Token**: The API token you generated in the previous step
8. Click **Add** to save the configuration.

## Configuration

### Creating a Package Group

1. Navigate to **Packages** > **Package Groups** in your Blesta admin panel.
2. Click **Create Group** to create a new package group.
3. Provide the following details:
   - **Name**: Enter a descriptive name (e.g., "UpCloud VPS Plans")
   - **Description**: Optional description for internal use
   - **Type**: Select "Standard"
4. Configure other settings as needed for your business requirements.
5. Click **Save** to create the package group.

### Creating a New Package

1. Navigate to **Packages** > **Packages**
2. Click **Create Package**
2. Configure the basic package details:
   - **Name**: Provide a descriptive package name (e.g., "UpCloud VPS - 2GB RAM")
   - **Description**: Detailed description for clients
   - **Status**: Set to "Active"
3. Set up pricing for the package according to your business model.
4. Click **Module** tab to proceed to module settings.

### Module Settings

On the module settings page, configure the following:

1. **Module**: Select the UpCloud module.
2. **Account**: Select the UpCloud account you created earlier.
3. **Server Plan**: Select from available UpCloud server plans or choose custom configuration.
4. **Template Settings**:
   - **Set Template**: Choose whether admin sets template or client can choose
   - **Template**: Select default operating system template

### Pricing Considerations

While UpCloud has predefined pricing for resources, you have the flexibility to:

- Set custom pricing for different server plans
- Add surcharges for premium templates (Windows)
- Configure location-based pricing variations
- Set up bandwidth overage charges
