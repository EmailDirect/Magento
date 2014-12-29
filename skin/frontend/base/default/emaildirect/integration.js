removeManageNewsletter = function(){
	var newsletter = $$('div.block-content ul li a[href*="newsletter/manage"]');
	if(newsletter.length){
		newsletter.first().up().remove();
	}
}

document.observe("dom:loaded", function() {

	var emaildirectEnabled = $$('div.block-content ul li a[href*="emaildirect/customer_account/index"]');

	if(emaildirectEnabled.length){
		removeManageNewsletter();
		var editLink = $$('div.my-account a[href*="newsletter/manage"]');
		if(editLink.length){
			editLink.first().writeAttribute('href', emaildirectEnabled.first().readAttribute('href'));
		}
	}
});