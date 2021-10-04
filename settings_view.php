<div class="wrap">
    <h1>MHB backup settings</h1>

    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="filename">Output File Name</label></th>
                <td><input name="filename" type="text" id="filename" value="" class="regular-text"
                           placeholder="Default: backup"></td>
            </tr>

            <tr>
                <th scope="row"><label for="exclusions">Exclusions Files</label></th>
                <td><textarea name="exclusions" type="text" id="exclusions" value="" class="regular-text" rows="4"
                              placeholder="Files or directories that you do not want to be in the output file. (e.g. wp-content/uploads, wp-config.php)"></textarea>
                    <p class="description">Separate with ,</p></td>
            </tr>

            <tr>
                <th scope="row"><label for="included">Included Files</label></th>
                <td><textarea name="included" type="text" id="included" value="" class="regular-text" rows="4"
                              placeholder="Default: all files."></textarea>
                    <p class="description">Separate with ,</p></td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit_backup" class="button button-primary" value="Download Backup Files">
            <span style="display: block; margin-top: 10px;">The backup file creation operation may be time consuming, please wait.</span>
        </p>
    </form>

    <br>
    <h2 class="title">Database Backup</h2>
    <br>

    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="filename">Output File Name</label></th>
                <td><input name="filename" type="text" id="filename" value="" class="regular-text"
                           placeholder="Default: DB-backup"></td>
            </tr>

            <tr>
                <th scope="row"><label for="exclusions">Exclusions Tables</label></th>
                <td><textarea name="exclusions" type="text" id="exclusions" value="" class="regular-text" rows="4"
                              placeholder="Tables that you do not want to be in the output file."></textarea>
                    <p class="description">Separate with ,</p></td>
            </tr>

            <tr>
                <th scope="row"><label for="included">Included Tables</label></th>
                <td><textarea name="included" type="text" id="included" value="" class="regular-text" rows="4"
                              placeholder="Default: all tables."></textarea>
                    <p class="description">Separate with ,</p></td>
            </tr>
        </table>

        <p class="submit"><input type="submit" name="submit_DB_backup" class="button button-primary"
                                 value="Download Database Backup"></p>
    </form>
</div>