/**
 * Calculate natural distance between two lat/lng points
 * @author Josh Lockhart
 * @version 1.0
 */
var R = 6371; // Radius of the earth in km
var toRad = function (degree) {
    return degree * Math.PI / 180;
};
var getDistance = function (lat1, lon1, lat2, lon2) {
    var dLat = toRad(lat2-lat1);  // Javascript functions in radians
    var dLon = toRad(lon2-lon1);
    var a = Math.sin(dLat/2) * Math.sin(dLat/2) + 
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // Distance in km
};