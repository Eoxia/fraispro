$file = 'C:\wamp64\www\dolibarr23\htdocs\custom\fraispro\test_image.jpg'
$uri = 'http://localhost/dolibarr23/htdocs/custom/fraispro/view/frontend/app.php?action=uploadPhoto'
$form = @{
    sub_dir = 'tmp/fast_capture_1'
    module_name = 'fraispro'
    userfile = Get-Item -Path $file
}
$response = Invoke-RestMethod -Uri $uri -Method Post -Form $form
$response | ConvertTo-Json
