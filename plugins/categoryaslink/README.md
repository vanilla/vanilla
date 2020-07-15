# Category As Link

Some Forum admins would like to be able to display what appears to be a Category in the list of Categories but that is, in fact, a link to another Category or even another page on another web property (e.g another forum, a knowledge base, a wiki, etc.).

***This Plugin's priority is set to high because the categoryUrl() function has to execute before the categoryUrl() in the SubCommunities plugin.***

### Category As Link

- **Summary:** Allow admins to configure a Category so that it links to another URL instead of displaying a Category.
- **Use case:** When a Forum wants to be able to direct users to another web property from the list of Categories on their Forum.
- **Description:** 
	- Add a column to the Category table to store the RedirectURL that a Category should link to instead of the Category page.
	- Add an interface on the Category add/edit page in the dashboard to capture the URL.
	- Add CSS classes to Categories that are being displayed as links ("Aliased AliasedCategory"), remove all the count data.
	- Override the CategoryURL function to change the URL to the RedirectURL
	- Redirect to the RedirectURL when someone requests the Category. 
- **Configs set or added:** none
- **Events used:**
	- `addEditCategory`: Inject HTML interface to capture the RedirectURL of Category.
	- `categoriesController_render_before`: Loop through Category Tree data and add CSS properties and remove the properties like Discussion Count and Comment Count when displaying link Categories.
	- `beforeCategoriesRender`: If a requested Category has a RedirectUrl, redirect it with a 301 code.
- **Setup steps:**
    1. Turn on the plugin.
    2. Either add or edit a Category.
    3. Input a link to another Category or anothe site.
    4. If you put a site from another domain, make sure to add it to Trusted Domains in the Security section of the Dashboard.
- **QA steps:**
    1. Follow the steps of Setup above.
    2. Navigate to a linked Category and click on it.
    3. Link to other domains, make sure that the Trusted Domains is working.
    4. Try to request a linked Category directly to make sure you are sent to the RedirectURL.