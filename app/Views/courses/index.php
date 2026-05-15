<?php
/**
 * Courses Index View
 * - Displays synced courses table
 * - Sync handled via AJAX (mcs_sync)
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- PAGE HEADER -->
<div class="page-header row no-gutters py-4" style="padding-top: 0 !important; padding-bottom: 0 !important;">
    <div class="col-6 text-sm-left mb-4 mb-sm-0">
        <h3 class="page-title">Courses</h3>
    </div>
    <div class="col-6 d-flex align-items-center justify-content-sm-end justify-content-center">
        <div class="d-flex gap-2" role="group" aria-label="Page actions">

            <button type="button" id="coursetransit-sync-courses" class="button button-secondary">
                Sync Courses
            </button>

        </div>
    </div>
</div>

<!-- COURSES TABLE -->
<div class="row">
    <div class="col-12">
        <div class="card" style="max-width: 100% !important;">
            <div class="card-body p-0 mt-2 table-responsive">
                <table id="coursetransit-courses-table" class="table table-striped table-bordered w-100 mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Course Name</th>
                            <!-- <th>Shortname</th> -->
                            <!-- <th>Status</th> -->
                            <th>Status</th>
                            <th>Last Synced</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ACTIVITIES MODAL -->

<div class="modal fade" id="coursetransitActivitiesModal" tabindex="-1" role="dialog" aria-hidden="true"
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

            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="overflow-y:auto;padding:20px;">

                <div id="coursetransit-activities-loading" style="text-align:center;padding:40px 0;">
                    Loading…
                </div>

                <div id="coursetransit-activities-content" class="d-none">

                    <!-- COURSE HEADER -->
                    <div style="display:flex;gap:14px;margin-bottom:24px;">
                        <img id="course-image" src=""
                            style="width:72px;height:72px;border-radius:10px;object-fit:cover;background:#f3f4f6;border:1px solid #e5e7eb;">
                        <div style="flex:1;">
                            <div id="course-name" class="ct-font" style="font-weight:600;font-size:17px;"></div>
                            <div id="course-shortname" style="font-size:13px;color:#6b7280;margin-top:2px;"></div>

                            <div style="margin-top:8px;">
                                <span id="course-synced" style="font-size:12px;color:#6b7280;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- META GRID -->
                    <div class="ct-font"
                        style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px;font-size:14px;">
                        <div><strong>Moodle Course:</strong> <span id="course-moodle-id"></span></div>
                        <div><strong>Sections:</strong> <span id="course-sections"></span></div>
                        <div><strong>Language:</strong> <span id="course-lang"></span></div>
                        <div><strong>Grades:</strong> <span id="course-grades"></span></div>
                        <div><strong>Start:</strong> <span id="course-start"></span></div>
                        <div><strong>End:</strong> <span id="course-end"></span></div>
                    </div>

                    <!-- CURRICULUM -->
                    <div style="margin-bottom:16px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">Curriculum</div>

                        <div class="accordion" id="coursetransit-curriculum-accordion"></div>

                        <div id="coursetransit-no-activities" class="text-center text-muted d-none"
                            style="padding:20px 0;">
                            No activities found.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QUICK EDIT MODAL -->
<div class="modal fade" id="coursetransitQuickEditModal" tabindex="-1" role="dialog" aria-hidden="true"
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

            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title">Quick Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="overflow-y:auto;padding:20px;">

                <!-- LOADING -->
                <div id="qe-loading" style="text-align:center;padding:40px 0;">
                    Loading…
                </div>

                <!-- CONTENT -->
                <div id="qe-content" class="d-none">

                    <!-- COURSE HEADER (same style) -->
                    <div style="display:flex;gap:14px;margin-bottom:24px;">
                        <img id="qe_course-image" src=""
                            style="width:72px;height:72px;border-radius:10px;object-fit:cover;background:#f3f4f6;border:1px solid #e5e7eb;">
                        <div style="flex:1;">
                            <div id="qe_course-name" style="font-weight:600;font-size:17px;"></div>
                            <div id="qe_course-shortname" style="font-size:13px;color:#6b7280;margin-top:2px;"></div>

                            <div style="margin-top:8px;">
                                <span id="qe_course-status" class="badge bg-secondary"></span>
                                <span id="qe_course-synced"
                                    style="font-size:12px;color:#6b7280;margin-left:8px;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- QUICK EDIT FORM -->
                    <div style="display:grid;gap:12px;">

                        <!-- Title -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">Title</label>
                            <input type="text" id="qe-name" class="form-control">
                        </div>

                        <!-- Slug -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">Slug</label>
                            <input type="text" id="qe-slug" class="form-control">
                        </div>

                        <!-- Price -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">Regular Price</label>
                            <input type="number" id="qe-price" class="form-control">
                        </div>

                        <div>
                            <label style="font-size:12px;color:#6b7280;">Sale Price</label>
                            <input type="number" id="qe-sale-price" class="form-control">
                        </div>

                        <!-- SKU -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">SKU</label>
                            <input type="text" id="qe-sku" class="form-control">
                        </div>

                        <!-- Visibility -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">Visibility</label>
                            <select id="qe-visibility" class="form-control">
                                <option value="visible">Catalog & search</option>
                                <option value="catalog">Catalog only</option>
                                <option value="search">Search only</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>

                        <!-- Featured -->
                        <label>
                            <input type="checkbox" id="qe-featured"> Featured
                        </label>

                        <!-- Status -->
                        <div>
                            <label style="font-size:12px;color:#6b7280;">Status</label>
                            <select id="qe-status" class="form-control">
                                <option value="publish">Published</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>

                        <!-- <div style="display:flex;gap:10px;margin-top:14px;"> -->
                        <div style="display:flex;gap:10px;margin-top:14px;justify-content:flex-end;">

                            <button type="button" id="qe-cancel" class="button button-secondary">
                                Cancel
                            </button>
                            <button id="qe-save" class="button button-primary">
                                Save
                            </button>


                        </div>
                        <div style="margin-top:12px;font-size:12px;color:#6b7280;">
                            Need more options?
                            <a href="#" id="qe-full-edit" style="color:#0073aa;text-decoration:none;">
                                Open full edit page
                            </a>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

