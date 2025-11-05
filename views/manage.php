<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="clearfix">
                            <div class="pull-left">
                                <h4 class="no-margin">
                                    <i class="fa fa-facebook"></i> <?php echo $title; ?>
                                </h4>
                            </div>
                            <div class="pull-right">
                                <?php if (has_permission('Sm_posters', '', 'create')) { ?>
                                    <a href="<?php echo admin_url('Sm_posters/add'); ?>" class="btn btn-primary">
                                        <i class="fa fa-plus"></i> Add Connection
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                        <?php if (!empty($connections)) { ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Page Name</th>
                                            <th>Page ID</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($connections as $conn) { ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('clients/client/' . $conn->client_id); ?>">
                                                        <?php echo $conn->company; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $conn->page_name ?: '-'; ?></td>
                                                <td><?php echo $conn->page_id; ?></td>
                                                <td>
                                                    <?php if ($conn->status == 1) { ?>
                                                        <span class="label label-success">Active</span>
                                                    <?php } else { ?>
                                                        <span class="label label-danger">Inactive</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo _dt($conn->created_at); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <?php if (has_permission('Sm_posters', '', 'edit')) { ?>
                                                            <a href="<?php echo admin_url('sm_posters/edit/' . $conn->id); ?>" 
                                                               class="btn btn-default btn-sm">
                                                                <i class="fa fa-pencil"></i>
                                                            </a>
                                                            <a href="<?php echo admin_url('Sm_posters/toggle_status/' . $conn->id); ?>" 
                                                               class="btn btn-default btn-sm">
                                                                <i class="fa fa-power-off"></i>
                                                            </a>
                                                        <?php } ?>
                                                        <?php if (has_permission('Sm_posters', '', 'delete')) { ?>
                                                            <a href="<?php echo admin_url('Sm_posters/delete/' . $conn->id); ?>" 
                                                               class="btn btn-danger btn-sm"
                                                               onclick="return confirm('Are you sure?');">
                                                                <i class="fa fa-trash"></i>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-info text-center">
                                <p>No Facebook connections yet.</p>
                                <a href="<?php echo admin_url('Sm_posters/add'); ?>" class="btn btn-primary">
                                    Add Your First Connection
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>