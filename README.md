Please follow these instructions to proceed with the installation of your extension:

### #1 Installation:

1. Copy all files into your magento directory and turn Magento cache off. 
2. In case you have different then “default” default folder - copy extensions folders to your default theme 
3. Navigate to System > Configuration > Wagento > Transfer Content Integration. 
[]()


### #2 Consumer key and Secret key(Create in Website B)

	1) First we want to register an Oauth consumer to get the consumer key and secret key.

	2) Login to the admin of the magento shop and from the menu

	System->Web Services->REST - Oauth Consumers and add a new Ouath 	consumer.



			3) We need the Key and Secret which is what we pass in the

		System > Configuration > Wagento > Transfer Content Integration. 

### #3 Creating a REST role for the Content Tranfer Api(Create in Website B)

	1) Click **System** > **Web Services** > **REST – Roles**.

	2) On the REST—Roles page, click **Add Admin Role**. 

3) In the **Role Name** field, enter Content Transfer Demo. 

4) Click **Save Role**. 

5) In the left navigation bar, click **Role API Resources**.6) The Role Resources page contains a hierarchical list of resources to which you can grant or deny the 
Content Transfer Demo role access. 

7) From the **Resource Access** list, click **Custom**. 

8) Select the check box next to the node labeled **Content Transfer API**.

	Magento automatically checks the child check boxes as the following figure shows.



	9) Click **Save Role**.
	Magento saves the resource API permissions you granted to the Content Transfer 	Demo REST role. 
	The Content Transfer Demo role now has permission to use the Cms Pages and Cms 	Static Blocks.

	10) In the left navigation bar, click **Role Users**. Click**Reset Filter** (in the upper-right 	corner of the page). 
	The page displays all registered users as the following figure shows.



	11) Select the check box next to each user to grant the user privileges to access the resources 	available to the Content Transfer Demo REST role—that is, permission to call the 	Content Transfer API.

	12) When you’re done, click **Save Role**.
	The specified user(s) can now grant an external program the right to call the Content 	Transfer API.

### #4 System Configuration

Navigate to System > Configuration > Wagento > Transfer Content Integration. 

- REST API Source Host – Website A Host Name.
- REST API Destination Host – Website B Host Name.
- Consumer Key - Website B consumer key for oauth authentication.
- Consumer Secret - Website B consumer Secret for oauth authentication.
### #5 Running the Module(In Website A)

1. Navigate CMS >> Pages.
1. Click **Sync Pages** (in the upper-right corner of the page).
1. It will redirect to Website B and prompt login screen.
1. Enter the login credentials of the OAuth consumer.
1. Click **Login**.
1. When prompted, click **Authorize** to grant authorization for the script to access your OAuth consumer account, as the following figure shows.

	

1. Click Authorize, this update all the Cms Pages content from Website B to Website A

8. To update Cms Static Blocks, Please follow same steps after navigation to CMS >> Static Blocks.