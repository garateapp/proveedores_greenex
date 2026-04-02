import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavItem, type NavSection } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

interface NavMainProps {
    primaryItem: NavItem;
    sections: NavSection[];
}

export function NavMain({ primaryItem, sections }: NavMainProps) {
    const page = usePage();

    const currentPath = page.url.split('?')[0];

    const isItemActive = (item: NavItem): boolean => {
        const itemPath = resolveUrl(item.href);
        return currentPath === itemPath || currentPath.startsWith(`${itemPath}/`);
    };

    const isSectionActive = (section: NavSection): boolean =>
        section.items.some((item) => isItemActive(item));

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Proveedores</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        isActive={isItemActive(primaryItem)}
                        tooltip={{ children: primaryItem.title }}
                    >
                        <Link href={primaryItem.href} prefetch>
                            {primaryItem.icon && <primaryItem.icon />}
                            <span>{primaryItem.title}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

                {sections.map((section) => (
                    <Collapsible
                        key={section.title}
                        asChild
                        defaultOpen={section.defaultOpen ?? isSectionActive(section)}
                        className="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger asChild>
                                <SidebarMenuButton
                                    tooltip={{ children: section.title }}
                                    isActive={isSectionActive(section)}
                                >
                                    {section.icon && <section.icon />}
                                    <span>{section.title}</span>
                                    <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    {section.items.map((item) => (
                                        <SidebarMenuSubItem key={`${section.title}-${item.title}`}>
                                            <SidebarMenuSubButton asChild isActive={isItemActive(item)}>
                                                <Link href={item.href} prefetch>
                                                    {item.icon && <item.icon />}
                                                    <span>{item.title}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    ))}
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
