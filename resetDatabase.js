const { exec } = require('child_process');

// Replace 'php' with the full path to your PHP executable
const phpPath = 'D:\\Workspaces\\bin\\php\\php8.2.13\\php.exe';
exec(`${phpPath} bin/console doctrine:database:drop --force && ${phpPath} bin/console doctrine:database:create && ${phpPath} bin/console doctrine:schema:update --force && ${phpPath} bin/console doctrine:fixtures:load --no-interaction`, (err, stdout, stderr) => {
    if (err) {
        console.error(`Erreur lors de la réinitialisation de la base de données: ${stderr}`);
        process.exit(1);
    }
    console.log(`Base de données réinitialisée: ${stdout}`);
});