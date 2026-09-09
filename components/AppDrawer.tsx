/**
 * App Drawer Component
 * Slide-out navigation menu with role-based items
 */

import React, { useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    TouchableOpacity,
    Image,
    Modal,
    Animated,
    Dimensions,
    Linking,
    Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { api } from '../services/api';
import { colors, fontSize, fontWeight, spacing, borderRadius, shadows } from '../constants/theme';
import { useAuth } from '../contexts/AuthContext';

const { width: SCREEN_WIDTH } = Dimensions.get('window');
const DRAWER_WIDTH = SCREEN_WIDTH * 0.75;

interface AppDrawerProps {
    isOpen: boolean;
    onClose: () => void;
    userName: string;
    userRole: string;
    userImage: string | null;
    onLogout: () => void;
    onProfilePress?: () => void;
}

interface MenuItemProps {
    icon: keyof typeof Ionicons.glyphMap;
    label: string;
    onPress: () => void;
    rightElement?: React.ReactNode;
}

function MenuItem({ icon, label, onPress, rightElement }: MenuItemProps) {
    return (
        <TouchableOpacity style={styles.menuItem} onPress={onPress} activeOpacity={0.7}>
            <View style={styles.menuIconContainer}>
                <Ionicons name={icon} size={24} color={colors.black} />
            </View>
            <Text style={styles.menuLabel}>{label}</Text>
            {rightElement}
        </TouchableOpacity>
    );
}

import { useLanguage } from '../contexts/LanguageContext';

export default function AppDrawer({ isOpen, onClose, userName, userRole, userImage, onLogout, onProfilePress }: AppDrawerProps) {
    const { getRole } = useAuth();
    const { t, language, setLanguage } = useLanguage();
    const role = getRole();

    const handleNavigation = (path: string) => {
        onClose();
        setTimeout(() => {
            router.push(path as any);
        }, 300);
    };

    const handleLogout = () => {
        onClose();
        setTimeout(() => {
            onLogout();
        }, 300);
    };

    const toggleLanguage = () => {
        setLanguage(language === 'en' ? 'hi' : 'en');
    };

    const handleContactSupervisor = useCallback(async () => {
        onClose();
        try {
            const res = await api.getMySupervisor();
            if (res.success && res.data?.number) {
                const num = String(res.data.number).replace(/\s+/g, '').replace(/[-–—]/g, '');
                if (num) {
                    await Linking.openURL('tel:' + num);
                } else {
                    Alert.alert('', t('supervisor_not_available'));
                }
            } else {
                Alert.alert('', t('supervisor_not_available'));
            }
        } catch {
            Alert.alert('', t('supervisor_not_available'));
        }
    }, [t, onClose]);

    return (
        <Modal
            visible={isOpen}
            transparent
            animationType="none"
            onRequestClose={onClose}
        >
            <View style={styles.overlay}>
                <TouchableOpacity style={styles.overlayTouchable} onPress={onClose} activeOpacity={1} />

                <View style={styles.drawer}>
                    {/* User Info: tappable to open profile edit */}
                    <TouchableOpacity
                        style={styles.userSection}
                        onPress={() => {
                            onClose();
                            if (onProfilePress) {
                                setTimeout(() => onProfilePress(), 300);
                            }
                        }}
                        activeOpacity={0.8}
                        disabled={!onProfilePress}
                    >
                        <View style={styles.headerRow}>
                            <View style={styles.userImageContainer}>
                                {userImage ? (
                                    <Image source={{ uri: userImage }} style={styles.userImage} />
                                ) : (
                                    <View style={styles.userImagePlaceholder}>
                                        <Ionicons name="person" size={40} color={colors.white} />
                                    </View>
                                )}
                            </View>
                            <View style={styles.welcomeBlock}>
                                <Text style={styles.welcomeText}>{t('welcome')}</Text>
                                <View style={styles.userNameRow}>
                                    <Text style={styles.userName}>{userName}</Text>
                                    <Text style={styles.userRoleText}>({t(role as any)})</Text>
                                </View>
                            </View>
                        </View>
                    </TouchableOpacity>

                    <View style={styles.divider} />

                    {/* Menu Items */}
                    <View style={styles.menuSection}>
                        {/* Admin specific */}
                        {role === 'admin' && (
                            <MenuItem
                                icon="grid-outline"
                                label={t('dashboard')}
                                onPress={() => handleNavigation('/(main)/admin')}
                            />
                        )}

                        {/* Superwiser specific */}
                        {role === 'superwiser' && (
                            <>
                                <MenuItem
                                    icon="grid-outline"
                                    label={t('dashboard')}
                                    onPress={() => handleNavigation('/(main)/supervisor')}
                                />
                                <MenuItem
                                    icon="cube-outline"
                                    label={t('fill_container')}
                                    onPress={() => handleNavigation('/(main)/supervisor/kitchen/cooker-to-container')}
                                />
                                <MenuItem
                                    icon="bus-outline"
                                    label={t('load_truck')}
                                    onPress={() => handleNavigation('/(main)/supervisor/kitchen/container-to-truck')}
                                />
                                <MenuItem
                                    icon="notifications-outline"
                                    label={t('notifications')}
                                    onPress={() => handleNavigation('/(main)/supervisor/notifications')}
                                />
                                <MenuItem
                                    icon="document-text-outline"
                                    label={t('reports')}
                                    onPress={() => handleNavigation('/(main)/supervisor/reports')}
                                />
                            </>
                        )}

                        {/* Driver & Admin - Route List */}
                        {(role === 'driver' || role === 'admin') && (
                            <>
                                <MenuItem
                                    icon="git-branch-outline"
                                    label={t('route_list')}
                                    onPress={() => handleNavigation('/(main)/driver/routes')}
                                />
                                <MenuItem
                                    icon="shield-checkmark-outline"
                                    label={t('food_safety')}
                                    onPress={() => handleNavigation('/(main)/driver/food-safety')}
                                />
                            </>
                        )}

                        {/* Driver only - Contact your supervisor (call) */}
                        {role === 'driver' && (
                            <MenuItem
                                icon="call-outline"
                                label={t('contact_your_supervisor')}
                                onPress={handleContactSupervisor}
                            />
                        )}

                        {/* Admin - Reports */}
                        {role === 'admin' && (
                            <MenuItem
                                icon="document-text-outline"
                                label={t('reports')}
                                onPress={() => handleNavigation('/(main)/admin/reports')}
                            />
                        )}

                        {/* Kitchen specific */}
                        {role === 'kitchen' && (
                            <>
                                <MenuItem
                                    icon="grid-outline"
                                    label={t('dashboard')}
                                    onPress={() => handleNavigation('/(main)/kitchen')}
                                />
                                <MenuItem
                                    icon="cube-outline"
                                    label={t('fill_container')}
                                    onPress={() => handleNavigation('/(main)/kitchen/cooker-to-container')}
                                />
                                <MenuItem
                                    icon="bus-outline"
                                    label={t('load_truck')}
                                    onPress={() => handleNavigation('/(main)/kitchen/container-to-truck')}
                                />
                            </>
                        )}

                        <View style={styles.menuDivider} />

                        <MenuItem
                            icon="language-outline"
                            label={language === 'en' ? 'Change to Hindi' : 'English में बदलें'}
                            onPress={toggleLanguage}
                        />
                    </View>

                    {/* Logout Button */}
                    <View style={styles.logoutSection}>
                        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout} activeOpacity={0.8}>
                            <Text style={styles.logoutText}>{t('logout')}</Text>
                            <Ionicons name="log-out-outline" size={24} color={colors.white} />
                        </TouchableOpacity>
                    </View>
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    overlay: {
        flex: 1,
        backgroundColor: 'rgba(0, 0, 0, 0.5)',
        flexDirection: 'row',
    },
    overlayTouchable: {
        flex: 1,
    },
    drawer: {
        position: 'absolute',
        left: 0,
        top: 0,
        bottom: 0,
        width: DRAWER_WIDTH,
        backgroundColor: colors.white,
        paddingTop: 60,
    },
    userSection: {
        paddingHorizontal: spacing.lg,
        paddingBottom: spacing.lg,
    },
    headerRow: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        gap: spacing.md,
    },
    userImageContainer: {
        marginBottom: 0,
    },
    userImage: {
        width: 64,
        height: 64,
        borderRadius: 32,
    },
    userImagePlaceholder: {
        width: 64,
        height: 64,
        borderRadius: 32,
        backgroundColor: colors.gray,
        justifyContent: 'center',
        alignItems: 'center',
    },
    welcomeBlock: {
        flex: 1,
        justifyContent: 'center',
        paddingTop: 4,
    },
    welcomeText: {
        fontSize: fontSize.sm,
        color: colors.blackLight,
        marginBottom: spacing.xs,
    },
    userNameRow: {
        flexDirection: 'row',
        alignItems: 'baseline',
        flexWrap: 'wrap',
    },
    userName: {
        fontSize: fontSize.xxl,
        fontWeight: fontWeight.bold,
        color: colors.black,
        marginRight: spacing.sm,
    },
    userRoleText: {
        fontSize: fontSize.md,
        fontWeight: fontWeight.bold,
        color: colors.black,
    },
    divider: {
        height: 1,
        backgroundColor: colors.gray,
        marginHorizontal: spacing.lg,
        marginBottom: spacing.lg,
    },
    menuSection: {
        flex: 1,
        paddingHorizontal: spacing.sm,
    },
    menuItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: spacing.md,
        paddingHorizontal: spacing.sm,
    },
    menuIconContainer: {
        width: 45,
        height: 38,
        backgroundColor: `${colors.orange}33`,
        borderTopLeftRadius: 5,
        borderTopRightRadius: 12,
        borderBottomLeftRadius: 5,
        borderBottomRightRadius: 5,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: spacing.md,
    },
    menuLabel: {
        fontSize: fontSize.md,
        color: colors.blackMedium,
    },
    menuDivider: {
        height: 1,
        backgroundColor: colors.gray,
        marginVertical: spacing.sm,
        opacity: 0.5,
    },
    logoutSection: {
        paddingHorizontal: spacing.xl,
        paddingVertical: spacing.xxxl,
        alignItems: 'center',
    },
    logoutButton: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: colors.delete,
        paddingHorizontal: spacing.xl,
        paddingVertical: spacing.md,
        borderRadius: borderRadius.md,
        gap: spacing.md,
    },
    logoutText: {
        fontSize: fontSize.md,
        fontWeight: fontWeight.bold,
        color: colors.white,
    },
});
