I wanted a progress bar in my phar updater. This led me to write my own for learning and for some code refactoring.

I borrowed some pieces from other packages, did a some refactoring or those borrowed methods, created a spudmanager separate from the spud symfony console command, and smashed together the manifest and update functionality into one package. Previously the package I was using depended on another package that did the actual updating. This cuts out one package dependeny.

At the moment it is missing the rollback and pre-release and public key options but those will be added.

# Credits
It is very important to me to give credit where credit is due. Therefore when this is actually ready for release I will give proper credit. It's just a WIP for now though.

