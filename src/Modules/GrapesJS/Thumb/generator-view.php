<style>
    html,
    body {
        margin: 0;
        padding: 0;
    }

    /* Hide PageBuilder placeholders while generating the thumbnail */
    [phpb-hide-if-not-editable] {
        display: none !important;
    }
</style>

<script src="<?= phpb_asset('pagebuilder/html2canvas-v0.4.1.min.js') ?>"></script>

<script>
$(function () {

    /**
     * Capture the current page and upload it as a thumbnail.
     */
    function generateThumbnail() {

        const $body = $("body");

        html2canvas($body, {
            allowTaint: false,
            useCORS: true,
            height: $body.outerHeight(),

            onrendered: function (canvas) {

                // Convert the rendered canvas into a Base64 image.
                const imageData = canvas.toDataURL("image/png");

                $.ajax({
                    type: "POST",
                    url: "<?= phpb_url('pagebuilder', ['route' => 'thumb_generator', 'action' => 'upload']) ?>",
                    data: {
                        block: "<?= $blockSlug ?>",
                        data: imageData
                    },

                    success: function () {
                        // Notify the parent window that the thumbnail
                        // has been successfully generated and uploaded.
                        window.parent.postMessage("thumb-saved", "*");
                    },

                    error: function (xhr, status, error) {
                        console.error("Thumbnail upload failed.", {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                    }
                });

            }
        });
    }

    // Start thumbnail generation once the document is ready.
    generateThumbnail();

});
</script>
