<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="no-margin">
                                    <i class="fa fa-history"></i> <?php echo $title; ?>
                                </h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('sm_posters/create_post'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Create New Post
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                        <?php if (!empty($posts)) { ?>
                            <div class="table-responsive">
                                <table class="table table-striped dataTable">
                                    <thead>
                                        <tr>
                                            <th>Message</th>
                                            <th>Platforms</th>
                                            <th>Media</th>
                                            <th>Status</th>
                                            <th>Scheduled/Published</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // SVG icons for platforms
                                        $platform_svg = [
                                            'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                                            
                                            'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
                                            
                                            'x' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                                            
                                            'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                                            
                                            'tumblr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M14.563 24c-5.093 0-7.031-3.756-7.031-6.411V9.747H5.116V6.648c3.63-1.313 4.512-4.596 4.71-6.469C9.84.051 9.941 0 9.999 0h3.517v6.114h4.801v3.633h-4.82v7.47c.016 1.001.375 2.371 2.207 2.371h.09c.631-.02 1.486-.205 1.936-.419l1.156 3.425c-.436.636-2.4 1.374-4.156 1.404h-.178l.011.002z"/></svg>',
                                            
                                            'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>'
                                        ];
                                        
                                        foreach ($posts as $post) { 
                                            $platforms = $this->sm_posters_model->get_post_platforms($post->id);
                                            
                                            // Custom character limiter function
                                            $message_preview = $post->message;
                                            if (strlen($message_preview) > 100) {
                                                $message_preview = substr($message_preview, 0, 100) . '...';
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <div style="max-width: 300px;">
                                                        <?php echo nl2br(htmlspecialchars($message_preview)); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($platforms)) { 
                                                        foreach ($platforms as $platform) {
                                                            $colors = [
                                                                'facebook' => '#4267B2',
                                                                'instagram' => '#E4405F',
                                                                'x' => '#000000',
                                                                'linkedin' => '#0077B5',
                                                                'tumblr' => '#35465C',
                                                                'pinterest' => '#BD081C'
                                                            ];
                                                            
                                                            $color = isset($colors[$platform->platform]) ? $colors[$platform->platform] : '#999';
                                                            $svg = isset($platform_svg[$platform->platform]) ? $platform_svg[$platform->platform] : '';
                                                            
                                                            $status_color = $platform->status == 'published' ? 'success' : ($platform->status == 'failed' ? 'danger' : 'warning');
                                                    ?>
                                                        <span class="platform-badge platform-badge-<?php echo $status_color; ?>" 
                                                              style="background-color: <?php echo $color; ?>;"
                                                              title="<?php echo ucfirst($platform->platform) . ': ' . ucfirst($platform->status); ?>">
                                                            <?php echo $svg; ?>
                                                        </span>
                                                    <?php } 
                                                    } else { ?>
                                                        <span class="text-muted">None</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if ($post->media_type == 'image') { ?>
                                                        <i class="fa fa-image text-success"></i> Image
                                                    <?php } elseif ($post->media_type == 'video') { ?>
                                                        <i class="fa fa-video-camera text-info"></i> Video
                                                    <?php } else { ?>
                                                        <i class="fa fa-file-text-o text-muted"></i> Text
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_labels = [
                                                        'draft' => 'default',
                                                        'scheduled' => 'info',
                                                        'publishing' => 'warning',
                                                        'published' => 'success',
                                                        'failed' => 'danger'
                                                    ];
                                                    $label_class = isset($status_labels[$post->status]) ? $status_labels[$post->status] : 'default';
                                                    ?>
                                                    <span class="label label-<?php echo $label_class; ?>">
                                                        <?php echo ucfirst($post->status); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($post->is_scheduled && $post->status == 'scheduled') { ?>
                                                        <i class="fa fa-clock-o text-info"></i> <?php echo _dt($post->scheduled_at); ?>
                                                    <?php } elseif ($post->published_at) { ?>
                                                        <i class="fa fa-check text-success"></i> <?php echo _dt($post->published_at); ?>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                            <i class="fa fa-cog"></i> <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu pull-right">
                                                            <li>
                                                                <a href="#" onclick="viewPostDetails(<?php echo $post->id; ?>); return false;">
                                                                    <i class="fa fa-eye"></i> View Details
                                                                </a>
                                                            </li>
                                                            <?php if ($post->status == 'scheduled') { ?>
                                                            <li>
                                                                <a href="<?php echo admin_url('sm_posters/edit_post/' . $post->id); ?>">
                                                                    <i class="fa fa-edit"></i> Edit
                                                                </a>
                                                            </li>
                                                            <?php } ?>
                                                            <li class="divider"></li>
                                                            <li>
                                                                <a href="<?php echo admin_url('sm_posters/delete_post/' . $post->id); ?>" 
                                                                   class="text-danger _delete">
                                                                    <i class="fa fa-trash"></i> Delete
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-info text-center">
                                <p><i class="fa fa-info-circle fa-2x"></i></p>
                                <p>No posts yet. Create your first post!</p>
                                <a href="<?php echo admin_url('sm_posters/create_post'); ?>" class="btn btn-primary mtop15">
                                    <i class="fa fa-plus"></i> Create Post
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Post Details Modal -->
<div class="modal fade" id="postDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Post Details</h4>
            </div>
            <div class="modal-body" id="postDetailsContent">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.platform-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    margin-right: 5px;
    margin-bottom: 3px;
}

.platform-badge svg {
    vertical-align: middle;
}

.platform-badge-success {
    opacity: 1;
}

.platform-badge-danger {
    opacity: 0.6;
}

.platform-badge-warning {
    opacity: 0.8;
}
</style>

<script>
function viewPostDetails(postId) {
    $('#postDetailsModal').modal('show');
    $('#postDetailsContent').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    
    $.get('<?php echo admin_url('sm_posters/get_post_details/'); ?>' + postId, function(response) {
        $('#postDetailsContent').html(response);
    });
}

$(document).ready(function() {
    if ($('.dataTable').length) {
        $('.dataTable').DataTable({
            order: [[4, 'desc']],
            pageLength: 25
        });
    }
});
</script>

<?php init_tail(); ?>
</body>
</html>