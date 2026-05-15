<?php
/**
 * Instructors Index View
 * - Displays instructors table
 * - CRUD handled via AJAX (instructors.js)
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- PAGE HEADER -->
<div class="page-header row no-gutters py-4" style="padding-top: 0 !important; padding-bottom: 0 !important;">
    <div class="col-6 text-sm-left mb-4 mb-sm-0">
        <h3 class="page-title">Instructors</h3>
    </div>

    <div class="col-6 d-flex align-items-center">
        <div class="d-inline-flex mb-sm-0 mx-auto ms-sm-auto me-sm-0" role="group" aria-label="Page actions">

            <!-- Add Instructor -->
            <button type="button" id="coursetransit-add-instructor" class="button button-secondary">
                Add Instructor
            </button>

        </div>
    </div>
</div>
<!-- INSTRUCTORS TABLE -->
<div class="row">
    <div class="col-12">

        <div class="card" style="max-width:100%!important;">
            <div class="card-body p-0 mt-2 table-responsive">

                <table id="coursetransit-instructors-table" class="table table-striped table-bordered w-100 mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Courses</th>
                            <!-- <th>Students</th> -->
                            <!-- <th>Rating</th> -->
                            <!-- <th>Status</th> -->
                            <th>Type</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>

    </div>
</div>

<!-- INSTRUCTOR MODAL (ADD / EDIT / VIEW) -->
<div class="modal fade" id="coursetransitInstructorModal" tabindex="-1" role="dialog" aria-hidden="true"
    style="pointer-events:auto;">

    <div class="modal-dialog" style="
            position:fixed;
            top:0;
            right:0;
            margin:0;
            height:100vh;
            width:460px;
            max-width:100%;
            transform:translateX(100%);
            transition:transform .3s ease-out;
         ">

        <div class="modal-content" style="height:100%;border-radius:0;border-left:1px solid #e5e7eb;">

            <!-- HEADER -->
            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title" id="coursetransit-instructor-modal-title">
                    Instructor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body" style="overflow-y:auto;padding:20px;">

                <!-- LOADER (same pattern as Orders) -->
                <div id="coursetransit-instructor-loading" style="text-align:center;padding:40px 0; display:none;">
                    Loading…
                </div>

                <!-- CONTENT WRAPPER (IMPORTANT — prevents flicker/shift) -->
                <div id="coursetransit-instructor-content">

                    <!-- FORM VIEW -->
                    <div id="coursetransit-instructor-form-wrap">
                        <form id="coursetransit-instructor-form">

                            <!-- Avatar -->
                            <div class="form-group text-center">
                                <img id="ins-avatar-preview" src="<?php echo \esc_url(COURSETRANSIT_URL . 'assets/images/avatar.png'); ?>"
                                    style="width:84px;height:84px;border-radius:50%;object-fit:cover;margin-bottom:10px;">
                                <br>
                                <button type="button" class="button button-secondary" id="ins-upload-avatar">
                                    Upload Avatar
                                </button>
                                <input type="hidden" name="avatar" id="ins-avatar">
                            </div>

                            <!-- Name -->
                            <div class="mb-3">
                                <label class="ct-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label class="ct-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>

                            <!-- Headline -->
                            <div class="mb-3">
                                <label class="ct-label">Headline</label>
                                <input type="text" class="form-control" name="headline">
                            </div>

                            <!-- Bio -->
                            <div class="mb-3">
                                <label class="ct-label">Bio</label>
                                <textarea class="form-control" name="bio" rows="4"></textarea>
                            </div>

                            <!-- Focus Areas -->
                            <div class="mb-3">
                                <label class="ct-label">Focus Areas</label>
                                <input type="text" class="form-control" name="focus_areas">
                            </div>

                            <!-- Expertise -->
                            <div class="mb-3">
                                <label class="ct-label">Expertise</label>
                                <input type="text" class="form-control" name="expertise">
                            </div>

                            <!-- Courses -->
                            <div class="mb-3">
                                <label class="ct-label">Courses</label>
                                <select id="ins-courses" name="courses[]" class="form-control" multiple
                                    style="width:100%;"></select>
                            </div>

                            <!-- Status -->
                            <!-- <div class="mb-3">
                                <label class="ct-label">Status</label>
                                <select class="form-control" name="is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div> -->

                            <!-- Public -->
                            <div class="mb-3">
                                <label class="ct-label">Public Profile</label>
                                <select class="form-control" name="is_public">
                                    <option value="1">Public</option>
                                    <option value="0">Private</option>
                                </select>
                            </div>

                            <input type="hidden" name="id" id="ins-id">

                        </form>
                    </div>

                    <!-- COURSES VIEW -->
                    <div id="coursetransit-instructor-courses" class="d-none">
                        <div id="coursetransit-instructor-courses-list"></div>
                    </div>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer" id="coursetransit-instructor-footer">
                <button type="button" class="button button-secondary mx-2" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="button button-primary" id="coursetransit-save-instructor">
                    Save
                </button>
            </div>

        </div>
    </div>
</div>