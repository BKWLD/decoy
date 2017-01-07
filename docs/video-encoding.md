# Video encoding

![](assets/img/encoding.gif)

The `Former::videoEncoder` form field creates the upload field for a video in the admin.  However, there is additional setup that the developer must do to make video encoding work.  Currently, only one provider is supported for video encoding, [Zencoder](https://zencoder.com/), but it's implementation is relatively abstracted; other providers could be added in the future.

You'll need to edit the Decoy "encoding.php" config file.  It should be within your app/configs/packages directory.  The comments for each config parameter should be sufficient to explain how to use them.  Depending on where you are pushing the encoded videos to, you may need to spin up an S3 instance.  If you push to SFTP you can generate a key-pair locally (`ssh-keygen`), post the private key to [Zencoder](https://app.zencoder.com/account/credentials) and then add the public key to the server's authorized_keys.

Then, models that support encoding should use the `Bkwld\Decoy\Models\Traits\Encodable` trait.  You also need to itemize each encodable attribute on the model by defining a `$encodable_attributes` property on the model.

```php?start_inline=1
class Marquee extends Base {
	use Bkwld\Decoy\Models\Traits\Encodable;
	protected $encodable_attributes = ['video'];
	protected $upload_attributes = ['video'];
}
```

You may want to add an accessor for building the video tag like:

```php?start_inline=1
	public function getVideoTagAttribute() {
		if (($encoding = $this->encoding()) && ($tag = $encoding->tag)) {
			return (string) $tag->preload();
		}
	}
```

You may want to use [Ngrok](https://ngrok.com/) to give your dev enviornment a public address so that Zencoder can pickup the files to convert.
