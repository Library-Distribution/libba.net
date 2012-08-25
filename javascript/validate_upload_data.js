function validate_upload_data()
{
	if (document.up.package.value != ""
		&& document.up.user.value != ""
		&& document.up.password.value != "")
	{
		document.up.submit_btn.disabled = false;
	}
	else
	{
		document.up.submit_btn.disabled = true;
	}
}