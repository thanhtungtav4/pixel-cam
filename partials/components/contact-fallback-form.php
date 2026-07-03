<?php
/**
 * Fallback contact form — dùng khi CF7 chưa cài/chưa có shortcode.
 * Demo UX giống pixel-cam/contact.html: submit hiện ticket ID, không gửi thật.
 * Khi nhập CF7 shortcode vào ACF field form_shortcode, form này tự ẩn.
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;
?>
<div class="ok" id="formOk">✓ <?php esc_html_e('Đã gửi yêu cầu. Mã ticket', 'underscores'); ?> <b id="ticketId">—</b>. <?php esc_html_e('Chúng tôi sẽ gọi lại trong 2 giờ làm việc.', 'underscores'); ?></div>
<form class="ct-fallback" id="contactForm" novalidate>
    <div class="frow">
        <div class="field">
            <label for="ctName"><?php esc_html_e('Họ và tên', 'underscores'); ?><span class="req">*</span></label>
            <input type="text" id="ctName" name="ctName" required placeholder="<?php esc_attr_e('Nguyễn Văn A', 'underscores'); ?>">
        </div>
        <div class="field">
            <label for="ctPhone"><?php esc_html_e('Số điện thoại', 'underscores'); ?><span class="req">*</span></label>
            <input type="tel" id="ctPhone" name="ctPhone" required placeholder="<?php esc_attr_e('0903 xxx xxx', 'underscores'); ?>">
        </div>
    </div>
    <div class="frow">
        <div class="field">
            <label for="ctEmail"><?php esc_html_e('Email', 'underscores'); ?></label>
            <input type="email" id="ctEmail" name="ctEmail" placeholder="<?php esc_attr_e('email@cua-ban.vn', 'underscores'); ?>">
        </div>
        <div class="field">
            <label for="ctSubject"><?php esc_html_e('Chủ đề', 'underscores'); ?><span class="req">*</span></label>
            <select id="ctSubject" name="ctSubject" required>
                <option value=""><?php esc_html_e('— Chọn chủ đề —', 'underscores'); ?></option>
                <option><?php esc_html_e('Tư vấn mua máy ảnh / lens', 'underscores'); ?></option>
                <option><?php esc_html_e('Báo giá doanh nghiệp · in hoá đơn VAT', 'underscores'); ?></option>
                <option><?php esc_html_e('Trả góp / Thu cũ đổi mới', 'underscores'); ?></option>
                <option><?php esc_html_e('Hợp tác nội dung / KOL', 'underscores'); ?></option>
                <option><?php esc_html_e('Đặt lịch ghé showroom', 'underscores'); ?></option>
                <option><?php esc_html_e('Khác', 'underscores'); ?></option>
            </select>
        </div>
    </div>
    <div class="frow">
        <div class="field">
            <label for="ctBudget"><?php esc_html_e('Ngân sách dự kiến', 'underscores'); ?></label>
            <select id="ctBudget" name="ctBudget">
                <option value=""><?php esc_html_e('— Chưa rõ —', 'underscores'); ?></option>
                <option><?php esc_html_e('Dưới 15 triệu', 'underscores'); ?></option>
                <option><?php esc_html_e('15 – 30 triệu', 'underscores'); ?></option>
                <option><?php esc_html_e('30 – 60 triệu', 'underscores'); ?></option>
                <option><?php esc_html_e('60 – 100 triệu', 'underscores'); ?></option>
                <option><?php esc_html_e('Trên 100 triệu', 'underscores'); ?></option>
            </select>
        </div>
        <div class="field">
            <label for="ctChannel"><?php esc_html_e('Cách liên hệ ưa thích', 'underscores'); ?></label>
            <select id="ctChannel" name="ctChannel">
                <option><?php esc_html_e('Gọi điện', 'underscores'); ?></option>
                <option><?php esc_html_e('Zalo', 'underscores'); ?></option>
                <option><?php esc_html_e('Messenger', 'underscores'); ?></option>
                <option><?php esc_html_e('Email', 'underscores'); ?></option>
            </select>
        </div>
    </div>
    <div class="field">
        <label for="ctMessage"><?php esc_html_e('Mô tả nhu cầu', 'underscores'); ?><span class="req">*</span></label>
        <textarea id="ctMessage" name="ctMessage" required placeholder="<?php esc_attr_e('Ví dụ: Mình cần combo máy ảnh + lens chân dung cho công việc chụp gia đình cuối tuần, ngân sách ~40 triệu...', 'underscores'); ?>"></textarea>
    </div>
    <label class="consent">
        <input type="checkbox" id="ctConsent" name="ctConsent" required>
        <span><?php esc_html_e('Tôi đồng ý cho Pixel Cam liên hệ về yêu cầu này. Thông tin chỉ dùng để tư vấn, không chia sẻ cho bên thứ ba.', 'underscores'); ?></span>
    </label>
    <div class="submit">
        <button type="submit"><?php esc_html_e('Gửi yêu cầu', 'underscores'); ?></button>
        <small><?php esc_html_e('Phản hồi trong 2 giờ làm việc', 'underscores'); ?></small>
    </div>
</form>
<script>
(function(){
    var f=document.getElementById('contactForm'),ok=document.getElementById('formOk');
    if(!f) return;
    f.addEventListener('submit',function(e){
        e.preventDefault();
        if(!f.checkValidity()){f.reportValidity();return;}
        var id='PX-'+Math.random().toString(36).slice(2,6).toUpperCase()+'-'+new Date().getFullYear();
        var t=document.getElementById('ticketId');
        if(t)t.textContent=id;
        if(ok){ok.classList.add('show');ok.scrollIntoView({behavior:'smooth',block:'center'});}
        f.reset();
    });
})();
</script>
