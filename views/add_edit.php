<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-facebook"></i> <?php echo $title; ?>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open(current_url()); ?>

                        <!-- Client Selection -->
                        <div class="form-group">
                            <label for="client_id" class="control-label">
                                <span class="text-danger">*</span> Client
                            </label>
                            <select name="client_id" id="client_id" class="selectpicker form-control" 
                                    data-live-search="true" required>
                                <option value="">-- Select Client --</option>
                                <?php foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client['userid']; ?>"
                                            <?php echo ($connection && $connection->client_id == $client['userid']) ? 'selected' : ''; ?>>
                                        <?php echo $client['company']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Page Name -->
                        <div class="form-group">
                            <label for="page_name" class="control-label">
                                Page Name (Optional)
                            </label>
                            <input type="text" 
                                   name="page_name" 
                                   id="page_name" 
                                   class="form-control" 
                                   value="<?php echo $connection ? $connection->page_name : ''; ?>"
                                   placeholder="e.g. My Business Page">
                        </div>

                        <!-- Page ID -->
                        <div class="form-group">
                            <label for="page_id" class="control-label">
                                <span class="text-danger">*</span> Facebook Page ID
                            </label>
                            <input type="text" 
                                   name="page_id" 
                                   id="page_id" 
                                   class="form-control" 
                                   value="<?php echo $connection ? $connection->page_id : ''; ?>"
                                   placeholder="Enter Facebook Page ID"
                                   required>
                            <p class="text-muted">
                                <small>Find your Page ID: Go to your Facebook Page → About → Page ID</small>
                            </p>
                        </div>

                        <!-- Access Token -->
                        <div class="form-group">
                            <label for="access_token" class="control-label">
                                <span class="text-danger">*</span> Page Access Token
                            </label>
                            <textarea name="access_token" 
                                      id="access_token" 
                                      rows="4" 
                                      class="form-control" 
                                      placeholder="Paste your Page Access Token here"
                                      required><?php echo $connection ? $connection->access_token : ''; ?></textarea>
                            <p class="text-muted">
                                <small>Get token from: developers.facebook.com → Your App → Tools → Access Token Tool</small>
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" 
                                    name="status" 
                                    id="status"
                                    value="1" 
                                    <?php if ($connection && $connection->status == 1) { echo 'checked'; } ?>
                                    <?php if (!$connection) { echo 'checked'; } ?>>
                                <label for="status">Active</label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-group mtop20">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check"></i> <?php echo $connection ? 'Update' : 'Save'; ?>
                            </button>
                            <a href="<?php echo admin_url('Sm_posters'); ?>" class="btn btn-default">
                                Cancel
                            </a>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>