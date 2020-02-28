MOD_NAME=$1
MOD_VERSION=$2
MOD_ARCHIVE_NAME=$3

mkdir -p $MOD_NAME/mods;

cp $MOD_ARCHIVE_NAME $MOD_NAME/mods

zip -r $MOD_NAME/$MOD_NAME-$MOD_VERSION.zip $MOD_NAME/*

rsync -r --exclude $MOD_NAME/mods $MOD_NAME solder:/var/www/solder-repo/mods/
