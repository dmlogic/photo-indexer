<?php
/**
 * This was causing all sorts of issues in service providers when the creds were not yet generated.
 */

namespace DmLogic\GooglePhotoIndex;

use Google\Photos\Library\V1\PhotosLibraryClient;

trait CreatesPhotoService
{
    protected $photoservice;

    public function photoservice()
    {
        if (!$this->photoservice) {
            $this->photoservice = new PhotosLibraryClient(
                [
                    'credentials' => (new GoogleOAuth)->getCredentials(),
                ]
            );
        }
        return $this->photoservice;
    }
}
